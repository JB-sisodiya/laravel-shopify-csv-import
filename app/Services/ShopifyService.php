<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ShopifyService
{
    private string $storeUrl;
    private string $accessToken;
    private string $graphqlEndpoint;
    private ?string $collectionId;

    public function __construct()
    {
        $this->storeUrl = config('shopify.store_url', '');
        $this->accessToken = config('shopify.access_token', '');
        $this->graphqlEndpoint = config('shopify.graphql_endpoint', '');
        $this->collectionId = config('shopify.collection_id') ?: '';
    }

    public function request(string $query, array $variables = []): array
    {
        if (empty($this->storeUrl) || empty($this->accessToken) || empty($this->graphqlEndpoint)) {
            throw new RuntimeException('Shopify Integration is not properly configured. Check your env settings.');
        }

        $payload = [
            'query' => $query,
        ];
        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        Log::info('Import Images Enabled', [
            'enabled' => config('shopify.import_images')
        ]);

        Log::info('Shopify GraphQL final variables', [
            'variables' => $variables
        ]);

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type'           => 'application/json',
        ])->post($this->graphqlEndpoint, $payload);

        if ($response->failed()) {
            $errorMessage = $response->body();
            Log::error("Shopify API HTTP request failed (Status {$response->status()}): {$errorMessage}");
            throw new RuntimeException("Shopify API HTTP request failed (Status {$response->status()}): {$errorMessage}");
        }

        $body = $response->json();

        if (isset($body['errors']) && !empty($body['errors'])) {
            Log::error("Shopify GraphQL returned errors: " . json_encode($body));
            $errs = array_map(fn($e) => $e['message'] ?? 'Unknown GraphQL error', $body['errors']);
            throw new RuntimeException('Shopify GraphQL Errors: ' . implode('; ', $errs));
        }

        return $body;
    }

    public function findProductByHandle(string $handle): ?array
    {
        $query = '
            query GetProductByHandle($query: String!) {
              products(first: 1, query: $query) {
                edges {
                  node {
                    id
                    handle
                    variants(first: 1) {
                      edges {
                        node {
                          id
                          inventoryItem {
                            id
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
        ';

        $body = $this->request($query, ['query' => "handle:{$handle}"]);
        $edges = $body['data']['products']['edges'] ?? [];

        foreach ($edges as $edge) {
            $node = $edge['node'] ?? [];
            if (($node['handle'] ?? null) === $handle) {
                return $node;
            }
        }

        return null;
    }

    public function getDefaultLocationId(): ?string
    {
        $query = '
            query GetLocations {
              locations(first: 1) {
                edges {
                  node {
                    id
                  }
                }
              }
            }
        ';

        $body = $this->request($query);
        return $body['data']['locations']['edges'][0]['node']['id'] ?? null;
    }

    public function createProduct(array $data): array
    {
        $query = '
            mutation productCreate($input: ProductInput!) {
              productCreate(input: $input) {
                product {
                  id
                  variants(first: 1) {
                    edges {
                      node {
                        id
                        inventoryItem {
                          id
                        }
                      }
                    }
                  }
                }
                userErrors {
                  field
                  message
                }
              }
            }
        ';

        $tags = is_string($data['tags'] ?? null)
            ? array_filter(array_map('trim', explode(',', $data['tags'])))
            : ($data['tags'] ?? []);

        $input = [
            'title'           => $data['title'],
            'handle'          => $data['handle'],
            'descriptionHtml' => $data['body_html'] ?? null,
            'vendor'          => $data['vendor'] ?? null,
            'productType'     => $data['product_type'] ?? null,
            'tags'            => $tags,
            'status'          => filter_var($data['published'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'ACTIVE' : 'DRAFT',
        ];

        $body = $this->request($query, ['input' => $input]);
        $result = $body['data']['productCreate'] ?? [];

        if (!empty($result['userErrors'])) {
            Log::error('Shopify productCreate userErrors: ' . json_encode($result['userErrors']));
            $errors = array_map(function($e) {
                $field = is_array($e['field'] ?? null) ? implode('.', $e['field']) : ($e['field'] ?? 'unknown');
                return "{$field}: {$e['message']}";
            }, $result['userErrors']);
            throw new RuntimeException('Shopify userErrors: ' . implode('; ', $errors));
        }

        $product = $result['product'] ?? null;
        if (!$product) {
            throw new RuntimeException('Failed to create product in Shopify (response empty).');
        }

        $variant = $product['variants']['edges'][0]['node'] ?? null;
        if (!$variant) {
            throw new RuntimeException('Failed to retrieve created variant from Shopify response.');
        }

        return [
            'id'                => $product['id'],
            'variant_id'        => $variant['id'],
            'inventory_item_id' => $variant['inventoryItem']['id'],
        ];
    }

    public function updateProduct(string $productId, array $data): void
    {
        $query = '
            mutation productUpdate($input: ProductInput!) {
              productUpdate(input: $input) {
                product {
                  id
                }
                userErrors {
                  field
                  message
                }
              }
            }
        ';

        $tags = is_string($data['tags'] ?? null)
            ? array_filter(array_map('trim', explode(',', $data['tags'])))
            : ($data['tags'] ?? []);

        $input = [
            'id'              => $productId,
            'title'           => $data['title'],
            'descriptionHtml' => $data['body_html'] ?? null,
            'vendor'          => $data['vendor'] ?? null,
            'productType'     => $data['product_type'] ?? null,
            'tags'            => $tags,
            'status'          => filter_var($data['published'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'ACTIVE' : 'DRAFT',
        ];

        $body = $this->request($query, ['input' => $input]);
        $result = $body['data']['productUpdate'] ?? [];

        if (!empty($result['userErrors'])) {
            Log::error('Shopify productUpdate userErrors: ' . json_encode($result['userErrors']));
            $errors = array_map(function($e) {
                $field = is_array($e['field'] ?? null) ? implode('.', $e['field']) : ($e['field'] ?? 'unknown');
                return "{$field}: {$e['message']}";
            }, $result['userErrors']);
            throw new RuntimeException('Shopify userErrors: ' . implode('; ', $errors));
        }
    }

    public function updateProductVariant(string $productId, string $variantId, array $data): void
    {
        $query = '
            mutation productVariantsBulkUpdate(
              $productId: ID!
              $variants: [ProductVariantsBulkInput!]!
              $idempotencyKey: String!
            ) {
              productVariantsBulkUpdate(productId: $productId, variants: $variants) @idempotent(key: $idempotencyKey) {
                product {
                  id
                }
                userErrors {
                  field
                  message
                }
              }
            }
        ';

        $inventoryPolicy = isset($data['variant_inventory_policy'])
            ? strtoupper(trim($data['variant_inventory_policy']))
            : null;
        if ($inventoryPolicy !== 'CONTINUE' && $inventoryPolicy !== 'DENY') {
            $inventoryPolicy = 'DENY';
        }

        $tracked = true;
        if (isset($data['variant_inventory_tracker']) && strtolower(trim($data['variant_inventory_tracker'])) === 'none') {
            $tracked = false;
        }

        $weightValue = isset($data['weight']) ? (float) $data['weight'] : 0.0;
        $weightUnit = isset($data['weight_unit']) ? $this->normalizeWeightUnit($data['weight_unit']) : 'KILOGRAMS';

        $variantInput = [
            'id'              => $variantId,
            'price'           => isset($data['price']) && $data['price'] !== '' ? (string) $data['price'] : null,
            'compareAtPrice'  => isset($data['compare_at_price']) && $data['compare_at_price'] !== '' ? (string) $data['compare_at_price'] : null,
            'taxable'         => isset($data['variant_taxable']) ? filter_var($data['variant_taxable'], FILTER_VALIDATE_BOOLEAN) : null,
            'inventoryPolicy' => $inventoryPolicy,
            'inventoryItem'   => [
                'sku'              => $data['sku'] ?? null,
                'tracked'          => $tracked,
                'requiresShipping' => isset($data['variant_requires_shipping']) ? filter_var($data['variant_requires_shipping'], FILTER_VALIDATE_BOOLEAN) : null,
                'measurement'      => [
                    'weight' => [
                        'value' => $weightValue,
                        'unit'  => $weightUnit,
                    ]
                ]
            ]
        ];

        $idempotencyKey = 'var_' . bin2hex(random_bytes(16));

        $body = $this->request($query, [
            'productId'      => $productId,
            'variants'       => [$variantInput],
            'idempotencyKey' => $idempotencyKey,
        ]);

        $result = $body['data']['productVariantsBulkUpdate'] ?? [];
        if (!empty($result['userErrors'])) {
            Log::error('Shopify productVariantsBulkUpdate userErrors: ' . json_encode($result['userErrors']));
            $errors = array_map(function($e) {
                $field = is_array($e['field'] ?? null) ? implode('.', $e['field']) : ($e['field'] ?? 'unknown');
                return "{$field}: {$e['message']}";
            }, $result['userErrors']);
            throw new RuntimeException('Shopify userErrors: ' . implode('; ', $errors));
        }
    }

    public function getInventoryQuantity(string $inventoryItemId, string $locationId): int
    {
        $query = '
            query getInventoryByLocation($inventoryItemId: ID!, $locationId: ID!) {
              inventoryItem(id: $inventoryItemId) {
                inventoryLevel(locationId: $locationId) {
                  quantities(names: ["available"]) {
                    quantity
                  }
                }
              }
            }
        ';

        $body = $this->request($query, [
            'inventoryItemId' => $inventoryItemId,
            'locationId'      => $locationId,
        ]);

        $quantities = $body['data']['inventoryItem']['inventoryLevel']['quantities'] ?? [];
        foreach ($quantities as $q) {
            return (int) ($q['quantity'] ?? 0);
        }
        return 0;
    }

    public function setInventory(string $inventoryItemId, string $locationId, int $quantity): void
    {
        $currentQuantity = $this->getInventoryQuantity($inventoryItemId, $locationId);
        $delta = $quantity - $currentQuantity;

        if ($delta === 0) {
            return;
        }

        $query = '
            mutation InventoryAdjustExample(
              $inventoryItemId: ID!
              $locationId: ID!
              $delta: Int!
              $changeFromQuantity: Int
              $idempotencyKey: String!
            ) {
              inventoryAdjustQuantities(
                input: {
                  name: "available"
                  reason: "correction"
                  changes: [
                    {
                      inventoryItemId: $inventoryItemId
                      locationId: $locationId
                      delta: $delta
                      changeFromQuantity: $changeFromQuantity
                    }
                  ]
                }
              ) @idempotent(key: $idempotencyKey) {
                inventoryAdjustmentGroup {
                  id
                }
                userErrors {
                  code
                  message
                  field
                }
              }
            }
        ';

        $idempotencyKey = 'inv_' . bin2hex(random_bytes(16));

        $variables = [
            'inventoryItemId'    => $inventoryItemId,
            'locationId'         => $locationId,
            'delta'              => $delta,
            'changeFromQuantity' => $currentQuantity,
            'idempotencyKey'     => $idempotencyKey,
        ];

        $body = $this->request($query, $variables);
        $result = $body['data']['inventoryAdjustQuantities'] ?? [];

        if (!empty($result['userErrors'])) {
            Log::error('Shopify inventoryAdjustQuantities userErrors: ' . json_encode($result['userErrors']));
            $errors = array_map(function($e) {
                $field = is_array($e['field'] ?? null) ? implode('.', $e['field']) : ($e['field'] ?? 'unknown');
                $code = $e['code'] ?? 'unknown_code';
                return "{$field} ({$code}): {$e['message']}";
            }, $result['userErrors']);
            throw new RuntimeException('Shopify userErrors: ' . implode('; ', $errors));
        }
    }

    public function addProductImage(string $productId, string $imageUrl, ?string $altText = null): void
    {
        if (config('shopify.import_images') === false) {
            Log::info("Image upload skipped because SHOPIFY_IMPORT_IMAGES is disabled.");
            return;
        }

        $query = '
            mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
              productCreateMedia(productId: $productId, media: $media) {
                media {
                  id
                }
                userErrors {
                  field
                  message
                }
              }
            }
        ';

        $mediaInput = [
            'mediaContentType' => 'IMAGE',
            'originalSource'   => $imageUrl,
        ];
        if (!empty($altText)) {
            $mediaInput['alt'] = $altText;
        }

        $body = $this->request($query, [
            'productId' => $productId,
            'media'     => [$mediaInput],
        ]);
        $result = $body['data']['productCreateMedia'] ?? [];

        if (!empty($result['userErrors'])) {
            Log::error('Shopify productCreateMedia userErrors: ' . json_encode($result['userErrors']));
            
            foreach ($result['userErrors'] as $err) {
                $msg = $err['message'] ?? '';
                if (str_contains($msg, 'trial accounts') || str_contains($msg, 'Select a plan to upload this file')) {
                    // TODO: On paid Shopify plans, media upload can be enabled.
                    throw new RuntimeException("Image upload skipped (trial store limitation).");
                }
            }

            $errors = array_map(function($e) {
                $field = is_array($e['field'] ?? null) ? implode('.', $e['field']) : ($e['field'] ?? 'unknown');
                return "{$field}: {$e['message']}";
            }, $result['userErrors']);
            Log::warning("Failed to add media to product {$productId}: " . implode('; ', $errors));
            throw new RuntimeException("Failed to add media: " . implode('; ', $errors));
        }
    }

    public function addToTargetCollection(string $productId): void
    {
        if (empty($this->collectionId)) {
            return;
        }

        $query = '
            mutation collectionAddProducts($id: ID!, $productIds: [ID!]!) {
              collectionAddProducts(id: $id, productIds: $productIds) {
                collection {
                  id
                }
                userErrors {
                  field
                  message
                }
              }
            }
        ';

        $collectionGid = str_starts_with($this->collectionId, 'gid://')
            ? $this->collectionId
            : "gid://shopify/Collection/{$this->collectionId}";

        $body = $this->request($query, [
            'id'         => $collectionGid,
            'productIds' => [$productId],
        ]);

        $result = $body['data']['collectionAddProducts'] ?? [];
        if (!empty($result['userErrors'])) {
            Log::error('Shopify collectionAddProducts userErrors: ' . json_encode($result['userErrors']));
            $errors = array_map(function($e) {
                $field = is_array($e['field'] ?? null) ? implode('.', $e['field']) : ($e['field'] ?? 'unknown');
                return "{$field}: {$e['message']}";
            }, $result['userErrors']);
            Log::warning("Failed to add product {$productId} to collection {$this->collectionId}: " . implode('; ', $errors));
        }
    }

    private function normalizeWeightUnit(?string $unit): string
    {
        $weightUnit = strtoupper(trim($unit ?? 'KG'));
        return match ($weightUnit) {
            'G', 'GRAM', 'GRAMS'          => 'GRAMS',
            'KG', 'KILOGRAM', 'KILOGRAMS' => 'KILOGRAMS',
            'LB', 'POUND', 'POUNDS'       => 'POUNDS',
            'OZ', 'OUNCE', 'OUNCES'       => 'OUNCES',
            default                       => 'KILOGRAMS',
        };
    }
}
