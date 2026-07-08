<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shopify Store URL
    |--------------------------------------------------------------------------
    |
    | The myshopify domain of your store, e.g. "my-store.myshopify.com".
    | Do NOT include "https://" or a trailing slash.
    |
    */
    'store_url' => env('SHOPIFY_STORE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Shopify Admin API Access Token
    |--------------------------------------------------------------------------
    |
    | The private-app or custom-app access token used to authenticate against
    | the Shopify Admin API. Keep this value secret.
    |
    */
    'access_token' => env('SHOPIFY_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Shopify API Version
    |--------------------------------------------------------------------------
    |
    | The Shopify Admin API version to target, e.g. "2025-01".
    | See: https://shopify.dev/docs/api/usage/versioning
    |
    */
    'api_version' => env('SHOPIFY_API_VERSION', '2025-01'),

    /*
    |--------------------------------------------------------------------------
    | Collection ID
    |--------------------------------------------------------------------------
    |
    | The Shopify collection (custom or smart) that products should be
    | associated with during the import process.
    |
    */
    'collection_id' => env('SHOPIFY_COLLECTION_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | GraphQL Endpoint
    |--------------------------------------------------------------------------
    |
    | The full Admin GraphQL API endpoint, assembled from store URL and version.
    | Override explicitly in .env if your store uses a non-standard path.
    |
    */
    'graphql_endpoint' => env(
        'SHOPIFY_GRAPHQL_ENDPOINT',
        'https://' . env('SHOPIFY_STORE_URL', '') . '/admin/api/' . env('SHOPIFY_API_VERSION', '2025-01') . '/graphql.json'
    ),

    /*
    |--------------------------------------------------------------------------
    | Image Import Settings
    |--------------------------------------------------------------------------
    |
    | Controls whether images from the CSV should be uploaded to Shopify.
    | Set to false on trial stores to prevent trial account limitation errors.
    |
    */
    'import_images' => (bool) env('SHOPIFY_IMPORT_IMAGES', false),
];
