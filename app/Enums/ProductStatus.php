<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the processing lifecycle of a single product row from the CSV.
 *
 */
enum ProductStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Successful = 'successful';
    case Failed     = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pending',
            self::Processing => 'Processing',
            self::Successful => 'Successful',
            self::Failed     => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return match($this) {
            self::Successful, self::Failed => true,
            default                        => false,
        };
    }

    /** Returns true only on a successful Shopify API push. */
    public function isSuccessful(): bool
    {
        return $this === self::Successful;
    }
}
