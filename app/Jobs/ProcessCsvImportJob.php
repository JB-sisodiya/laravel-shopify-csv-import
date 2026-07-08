<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Constants\ImportConstants;
use App\Enums\ImportAction;
use App\Enums\LogLevel;
use App\Enums\ProductStatus;
use App\Enums\UploadStatus;
use App\Models\ErrorLog;
use App\Models\ImportRecord;
use App\Models\Product;
use App\Models\Upload;
use App\Services\CsvParserService;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ProcessCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    public function __construct(private readonly Upload $upload)
    {
        $this->onQueue(ImportConstants::IMPORT_QUEUE);
    }

    public function handle(CsvParserService $csvParser): void
    {
        Log::info('[Import] Started', $this->ctx());

        $this->upload->update([
            'status'     => UploadStatus::Processing,
            'started_at' => now(),
        ]);

        $absolutePath = Storage::disk(ImportConstants::UPLOAD_DISK)->path($this->upload->file_path);

        try {
            $headers = $csvParser->readAndValidateHeaders($absolutePath);
            $headerMap = $csvParser->getHeaderMap($headers);
            Log::info('[Import] Headers validated', $this->ctx(['headers' => $headers]));
        } catch (InvalidArgumentException|RuntimeException $e) {
            Log::error('[Import] Header validation failed', $this->ctx(['error' => $e->getMessage()]));
            $this->markUploadFailed($e->getMessage(), $e);
            return;
        }

        $totalRows = 0;
        $successfulRows = 0;
        $failedRows = 0;

        try {
            foreach ($csvParser->streamRows($absolutePath, $headers, $headerMap) as $parsedRow) {
                $totalRows++;
                $rowNumber = $parsedRow['row_number'];

                Log::debug('[Import] Processing row', $this->ctx(['row' => $rowNumber]));

                try {
                    if ($this->processRow($parsedRow)) {
                        $successfulRows++;
                        Log::debug('[Import] Row OK', $this->ctx(['row' => $rowNumber]));
                    } else {
                        $failedRows++;
                        Log::warning('[Import] Invalid row', $this->ctx(['row' => $rowNumber]));
                    }
                } catch (Throwable $e) {
                    $failedRows++;
                    Log::error('[Import] Unexpected row error', $this->ctx([
                        'row'   => $rowNumber,
                        'error' => $e->getMessage(),
                    ]));
                    $this->persistError(
                        message:   "Unexpected error on row {$rowNumber}: {$e->getMessage()}",
                        exception: $e,
                        productId: null,
                        payload:   $parsedRow['raw'] ?? [],
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error('[Import] Fatal streaming error', $this->ctx(['error' => $e->getMessage()]));
            $this->persistError(
                message:   "Fatal error during CSV streaming: {$e->getMessage()}",
                exception: $e,
                productId: null,
                payload:   [],
            );
            $this->upload->update([
                'status'          => UploadStatus::Failed,
                'completed_at'    => now(),
                'total_rows'      => $totalRows,
                'processed_rows'  => $totalRows,
                'successful_rows' => $successfulRows,
                'failed_rows'     => $failedRows,
            ]);
            return;
        }

        $this->upload->update([
            'status'          => UploadStatus::Completed,
            'completed_at'    => now(),
            'total_rows'      => $totalRows,
            'processed_rows'  => $totalRows,
            'successful_rows' => $successfulRows,
            'failed_rows'     => $failedRows,
        ]);

        Log::info('[Import] Completed', $this->ctx([
            'total'      => $totalRows,
            'successful' => $successfulRows,
            'failed'     => $failedRows,
        ]));
    }

    public function failed(Throwable $e): void
    {
        Log::critical('[Import] Job failed with unhandled exception', $this->ctx([
            'exception' => $e->getMessage(),
        ]));

        try {
            $this->upload->update([
                'status'       => UploadStatus::Failed,
                'completed_at' => now(),
            ]);
            $this->persistError(
                message:   'Job failed with unhandled exception: ' . $e->getMessage(),
                exception: $e,
                productId: null,
                payload:   [],
                level:     LogLevel::Critical,
            );
        } catch (Throwable) {
            // Ignore cascading failures inside failed handler
        }
    }

    private function processRow(array $parsedRow): bool
    {
        $data = $parsedRow['data'];
        $rowNumber = $parsedRow['row_number'];
        $raw = $parsedRow['raw'];
        $handle = $data['handle'] ?? 'unknown';

        $validationError = $this->validateRowData($data, $rowNumber);
        $isValid = $validationError === null;

        if (!$isValid) {
            DB::transaction(function () use ($data, $rowNumber, $validationError, $raw): void {
                $product = Product::create([
                    'upload_id'          => $this->upload->id,
                    'shopify_product_id' => $data['shopify_product_id'] ?? null,
                    'handle'             => $data['handle']            ?? '',
                    'title'              => $data['title']             ?? '',
                    'body_html'          => $data['body_html']         ?? null,
                    'vendor'             => $data['vendor']            ?? null,
                    'product_type'       => $data['product_type']      ?? null,
                    'tags'               => $data['tags']              ?? null,
                    'sku'                => $data['sku']               ?? null,
                    'barcode'            => $data['barcode']           ?? null,
                    'price'              => $data['price']             ?? '0.00',
                    'compare_at_price'   => $data['compare_at_price']  ?? null,
                    'inventory_quantity' => $data['inventory_quantity'] ?? 0,
                    'weight'             => $data['weight']            ?? null,
                    'weight_unit'        => $data['weight_unit']       ?? null,
                    'image_url'          => $data['image_url']         ?? null,
                    'status'             => ProductStatus::Failed,
                ]);

                ImportRecord::create([
                    'upload_id'     => $this->upload->id,
                    'product_id'    => $product->id,
                    'row_number'    => $rowNumber,
                    'action'        => $this->determineAction($data),
                    'status'        => ProductStatus::Failed,
                    'error_message' => $validationError,
                ]);

                $this->persistError(
                    message:   $validationError ?? 'Row validation failed',
                    exception: null,
                    productId: $product->id,
                    payload:   $raw,
                    level:     LogLevel::Warning,
                );
            });

            Log::error("Product failed: {$handle} - {$validationError}");
            return false;
        }

        /** @var Product $product */
        $product = null;
        /** @var ImportRecord $importRecord */
        $importRecord = null;

        DB::transaction(function () use ($data, $rowNumber, &$product, &$importRecord): void {
            $product = Product::create([
                'upload_id'          => $this->upload->id,
                'shopify_product_id' => $data['shopify_product_id'] ?? null,
                'handle'             => $data['handle']            ?? '',
                'title'              => $data['title']             ?? '',
                'body_html'          => $data['body_html']         ?? null,
                'vendor'             => $data['vendor']            ?? null,
                'product_type'       => $data['product_type']      ?? null,
                'tags'               => $data['tags']              ?? null,
                'sku'                => $data['sku']               ?? null,
                'barcode'            => $data['barcode']           ?? null,
                'price'              => $data['price']             ?? '0.00',
                'compare_at_price'   => $data['compare_at_price']  ?? null,
                'inventory_quantity' => $data['inventory_quantity'] ?? 0,
                'weight'             => $data['weight']            ?? null,
                'weight_unit'        => $data['weight_unit']       ?? null,
                'image_url'          => $data['image_url']         ?? null,
                'status'             => ProductStatus::Pending,
            ]);

            $importRecord = ImportRecord::create([
                'upload_id'  => $this->upload->id,
                'product_id' => $product->id,
                'row_number' => $rowNumber,
                'action'     => $this->determineAction($data),
                'status'     => ProductStatus::Pending,
            ]);
        });

        Log::info("Product importing: {$handle}");

        try {
            $shopifyService = resolve(ShopifyService::class);
            $locationId = $shopifyService->getDefaultLocationId();
            $existingProduct = $shopifyService->findProductByHandle($handle);

            if ($existingProduct) {
                $shopifyProductId = $existingProduct['id'];
                $variantNode = $existingProduct['variants']['edges'][0]['node'] ?? null;
                $variantId = $variantNode['id'] ?? null;
                $inventoryItemId = $variantNode['inventoryItem']['id'] ?? null;

                if (!$variantId) {
                    throw new RuntimeException("Existing Shopify product variant has no ID.");
                }

                $shopifyService->updateProduct($shopifyProductId, $data);
                $action = ImportAction::Update;
            } else {
                $created = $shopifyService->createProduct($data);
                $shopifyProductId = $created['id'];
                $variantId = $created['variant_id'];
                $inventoryItemId = $created['inventory_item_id'];
                $action = ImportAction::Create;
            }

            $shopifyService->updateProductVariant($shopifyProductId, $variantId, $data);

            if ($locationId && $inventoryItemId && isset($data['inventory_quantity'])) {
                $shopifyService->setInventory($inventoryItemId, $locationId, (int) $data['inventory_quantity']);
            }

            $imageWarning = null;
            if (!empty($data['image_url'])) {
                try {
                    $shopifyService->addProductImage($shopifyProductId, $data['image_url'], $data['image_alt'] ?? null);
                } catch (Throwable $e) {
                    if (str_contains($e->getMessage(), 'Image upload skipped (trial store limitation)')) {
                        $imageWarning = 'Image upload skipped (trial store limitation).';
                    } else {
                        Log::warning("Product image upload failed: " . $e->getMessage());
                    }
                }
            }

            $shopifyService->addToTargetCollection($shopifyProductId);

            $product->update([
                'shopify_product_id' => $shopifyProductId,
                'status'             => ProductStatus::Successful,
            ]);

            $importRecord->update([
                'action'           => $action,
                'status'           => ProductStatus::Successful,
                'error_message'    => $imageWarning,
                'shopify_response' => [
                    'status'             => 'success',
                    'shopify_product_id' => $shopifyProductId,
                    'shopify_variant_id' => $variantId,
                    'warning'            => $imageWarning,
                ],
            ]);

            Log::info("Product imported/updated successfully: {$handle}", [
                'shopify_product_id' => $shopifyProductId,
                'shopify_variant_id' => $variantId,
            ]);

            return true;
        } catch (Throwable $e) {
            $product->update(['status' => ProductStatus::Failed]);
            $importRecord->update([
                'status'           => ProductStatus::Failed,
                'error_message'    => $e->getMessage(),
                'shopify_response' => ['status' => 'failed', 'error' => $e->getMessage()],
            ]);

            $this->persistError(
                message:   "Shopify import failed for product \"{$handle}\": {$e->getMessage()}",
                exception: $e,
                productId: $product->id,
                payload:   $raw,
            );

            Log::error("Product failed: {$handle} - {$e->getMessage()}");
            return false;
        }
    }

    private function validateRowData(array $data, int $rowNumber): ?string
    {
        if (empty($data['handle'])) {
            return "Row {$rowNumber}: 'Handle' is required and cannot be empty.";
        }

        if (empty($data['title'])) {
            return "Row {$rowNumber}: 'Title' is required and cannot be empty.";
        }

        if (isset($data['price']) && $data['price'] !== null && $data['price'] !== '') {
            if (!is_numeric($data['price'])) {
                return "Row {$rowNumber}: 'Variant Price' must be a valid number, got \"{$data['price']}\".";
            }
            if ((float) $data['price'] < 0) {
                return "Row {$rowNumber}: 'Variant Price' cannot be negative.";
            }
        }

        return null;
    }

    private function determineAction(array $data): ImportAction
    {
        return isset($data['shopify_product_id']) && !empty($data['shopify_product_id'])
            ? ImportAction::Update
            : ImportAction::Create;
    }

    private function markUploadFailed(string $message, ?Throwable $e = null): void
    {
        $this->upload->update([
            'status'       => UploadStatus::Failed,
            'completed_at' => now(),
        ]);

        $this->persistError($message, $e, null, [], LogLevel::Error);
        Log::error('[Import] Upload marked as Failed', $this->ctx(['reason' => $message]));
    }

    private function persistError(
        string    $message,
        ?Throwable $exception,
        ?int      $productId,
        array     $payload,
        LogLevel  $level = LogLevel::Error,
    ): void {
        ErrorLog::create([
            'upload_id'  => $this->upload->id,
            'product_id' => $productId,
            'level'      => $level,
            'source'     => self::class,
            'message'    => $message,
            'exception'  => $exception
                ? get_class($exception) . ': ' . $exception->getMessage() . "\n" . $exception->getTraceAsString()
                : null,
            'payload'    => !empty($payload) ? $payload : null,
        ]);
    }

    private function ctx(array $extra = []): array
    {
        return array_merge([
            'upload_id' => $this->upload->id,
            'file'      => $this->upload->original_filename,
        ], $extra);
    }
}
