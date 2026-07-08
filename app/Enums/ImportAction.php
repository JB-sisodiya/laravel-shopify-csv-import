<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the Shopify Admin API operation performed for a product row.
 *
 * The action is determined at runtime by the import service:
 * - Create: no matching product exists in Shopify (no shopify_product_id in the CSV row)
 * - Update: a shopify_product_id was present in the CSV row and the product exists
 *
 * The field is nullable in the DB because the action is only known once the queue worker begins processing the row.
 */
enum ImportAction: string
{
    case Create = 'create';
    case Update = 'update';

    public function label(): string
    {
        return match($this) {
            self::Create => 'Create',
            self::Update => 'Update',
        };
    }
}
