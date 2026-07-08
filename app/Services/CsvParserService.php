<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class CsvParserService
{
    private const array HEADER_ALIASES = [
        'handle'                      => ['handle'],
        'title'                       => ['title'],
        'body_html'                   => ['body (html)', 'body html', 'description', 'body'],
        'vendor'                      => ['vendor'],
        'product_type'                => ['type', 'product type'],
        'tags'                        => ['tags'],
        'sku'                         => ['variant sku', 'sku'],
        'barcode'                     => ['variant barcode', 'barcode'],
        'price'                       => ['variant price', 'price'],
        'compare_at_price'            => ['variant compare at price', 'compare at price'],
        'weight'                      => ['variant grams', 'grams', 'weight', 'variant weight'],
        'weight_unit'                 => ['variant weight unit', 'weight unit'],
        'inventory_quantity'          => ['variant inventory qty', 'inventory', 'inventory quantity'],
        'image_url'                   => ['image src', 'image url', 'image_url'],
        'shopify_product_id'          => ['id', 'shopify product id', 'shopify_product_id'],
        'published'                   => ['published'],
        'variant_requires_shipping'   => ['variant requires shipping', 'requires shipping'],
        'variant_taxable'             => ['variant taxable', 'taxable'],
        'variant_inventory_tracker'   => ['variant inventory tracker', 'inventory tracker'],
        'variant_inventory_policy'    => ['variant inventory policy', 'inventory policy'],
        'variant_fulfillment_service' => ['variant fulfillment service', 'fulfillment service'],
        'image_position'              => ['image position', 'position'],
        'image_alt'                   => ['image alt text', 'image alt', 'alt text'],
    ];

    public function readAndValidateHeaders(string $absolutePath): array
    {
        if (!file_exists($absolutePath)) {
            throw new RuntimeException("CSV file not found at: {$absolutePath}");
        }

        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV file for reading: {$absolutePath}");
        }

        try {
            $headers = fgetcsv($handle);
        } finally {
            fclose($handle);
        }

        if ($headers === false || $headers === null || $headers === [null]) {
            throw new InvalidArgumentException('The CSV file is empty or contains no header row.');
        }

        $headers = array_map(
            static fn (string $h): string => trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF"),
            $headers,
        );

        $normalizedHeaders = array_map(
            static fn (string $h): string => strtolower($h),
            $headers,
        );

        Log::info('CSV Headers', $normalizedHeaders);

        $hasHandle = false;
        foreach (self::HEADER_ALIASES['handle'] as $alias) {
            if (in_array($alias, $normalizedHeaders, true)) {
                $hasHandle = true;
                break;
            }
        }

        $hasTitle = false;
        foreach (self::HEADER_ALIASES['title'] as $alias) {
            if (in_array($alias, $normalizedHeaders, true)) {
                $hasTitle = true;
                break;
            }
        }

        $missing = [];
        if (!$hasHandle) {
            $missing[] = 'Handle';
        }
        if (!$hasTitle) {
            $missing[] = 'Title';
        }

        if (!empty($missing)) {
            $detectedList = implode("\n", array_map(fn($h) => "- {$h}", $headers));
            $missingList  = implode("\n", array_map(fn($m) => "- {$m}", $missing));

            throw new InvalidArgumentException(
                "Detected Columns:\n{$detectedList}\n\nMissing Required Columns:\n{$missingList}"
            );
        }

        return $headers;
    }

    public function getHeaderMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $originalHeader) {
            $normalized = strtolower(trim($originalHeader, " \t\n\r\0\x0B\xEF\xBB\xBF"));

            foreach (self::HEADER_ALIASES as $field => $aliases) {
                if (in_array($normalized, $aliases, true)) {
                    $map[$originalHeader] = $field;
                    break;
                }
            }
        }
        return $map;
    }

    public function streamRows(string $absolutePath, array $headers, array $headerMap): \Generator
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Cannot open CSV file for streaming: {$absolutePath}");
        }

        try {
            fgetcsv($handle); // skip header row
            $rowNumber = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $row = array_pad($row, count($headers), '');
                $raw = array_combine($headers, $row);

                yield [
                    'row_number' => $rowNumber,
                    'raw'        => $raw,
                    'data'       => $this->mapToProductData($raw, $headerMap),
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    public function mapToProductData(array $rawRow, array $headerMap): array
    {
        $data = [];

        foreach (array_keys(self::HEADER_ALIASES) as $field) {
            $data[$field] = null;
        }

        foreach ($rawRow as $originalHeader => $value) {
            if (!isset($headerMap[$originalHeader])) {
                continue;
            }

            $field = $headerMap[$originalHeader];
            $value = trim($value);

            $data[$field] = match ($field) {
                'tags' => $this->parseTags($value),

                'price',
                'compare_at_price' => $value !== ''
                    ? number_format((float) $value, 2, '.', '')
                    : null,

                'weight' => $value !== ''
                    ? number_format(abs((float) $value), 3, '.', '')
                    : null,

                'inventory_quantity' => $value !== '' ? (int) $value : 0,

                default => $value !== '' ? $value : null,
            };
        }

        if (
            isset($data['weight']) && $data['weight'] !== null
            && (!isset($data['weight_unit']) || $data['weight_unit'] === null)
        ) {
            $data['weight_unit'] = 'g';
        }

        return $data;
    }

    private function isEmptyRow(array $row): bool
    {
        return empty(
            array_filter($row, static fn (mixed $cell): bool => trim((string) $cell) !== '')
        );
    }

    private function parseTags(string $value): ?array
    {
        if ($value === '') {
            return null;
        }

        $tags = array_values(
            array_filter(
                array_map('trim', explode(',', $value)),
                static fn (string $tag): bool => $tag !== '',
            )
        );

        return empty($tags) ? null : $tags;
    }
}
