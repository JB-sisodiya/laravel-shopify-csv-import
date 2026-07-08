<?php

declare(strict_types=1);

namespace App\Constants;

final class ImportConstants
{
    public const int MAX_FILE_SIZE_MB = 10;
    public const int MAX_FILE_SIZE_BYTES = self::MAX_FILE_SIZE_MB * 1024 * 1024;

    public const array ALLOWED_MIME_TYPES = [
        'text/csv',
        'text/plain',
        'application/csv',
        'application/vnd.ms-excel',
    ];

    public const string FILE_EXTENSION = 'csv';
    public const string IMPORT_QUEUE = 'imports';
    public const int CHUNK_SIZE = 50;
    public const int MAX_JOB_ATTEMPTS = 3;
    public const int JOB_RETRY_DELAY_SECONDS = 5;

    public const int SHOPIFY_RATE_LIMIT_STATUS = 429;
    public const int SHOPIFY_COST_THRESHOLD = 100;
    public const int SHOPIFY_THROTTLE_DELAY_MS = 1000;

    public const string UPLOAD_DISK = 'local';
    public const string UPLOAD_DIRECTORY = 'uploads/csv';

    public const string DEFAULT_WEIGHT_UNIT = 'kg';
    public const int DEFAULT_INVENTORY_QUANTITY = 0;
}
