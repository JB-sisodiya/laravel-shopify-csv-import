<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                  $id
 * @property int                  $upload_id
 * @property string|null          $shopify_product_id
 * @property string               $handle
 * @property string               $title
 * @property string|null          $body_html
 * @property string|null          $vendor
 * @property string|null          $product_type
 * @property array<int, string>|null $tags
 * @property string|null          $sku
 * @property string|null          $barcode
 * @property string               $price
 * @property string|null          $compare_at_price
 * @property int                  $inventory_quantity
 * @property string|null          $weight
 * @property string|null          $weight_unit
 * @property string|null          $image_url
 * @property ProductStatus        $status
 * @property \Carbon\Carbon        $created_at
 * @property \Carbon\Carbon        $updated_at
 *
 * Computed accessors:
 * @property-read bool   $is_synced
 * @property-read bool   $has_discount
 *
 * @method static Builder<static> pending()
 * @method static Builder<static> processing()
 * @method static Builder<static> successful()
 * @method static Builder<static> failed()
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'shopify_product_id',
        'handle',
        'title',
        'body_html',
        'vendor',
        'product_type',
        'tags',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'inventory_quantity',
        'weight',
        'weight_unit',
        'image_url',
        'status',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status'            => ProductStatus::class,
            // JSON array — Shopify tags are stored as a parsed array.
            'tags'              => 'array',
            // Decimal strings preserve precision; cast to string keeps '10.00' not 10.
            'price'             => 'decimal:2',
            'compare_at_price'  => 'decimal:2',
            'weight'            => 'decimal:3',
            'inventory_quantity' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /** @return BelongsTo<Upload, $this> */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    /** @return HasMany<ImportRecord, $this> */
    public function importRecords(): HasMany
    {
        return $this->hasMany(ImportRecord::class);
    }

    /** @return HasMany<ErrorLog, $this> */
    public function errorLogs(): HasMany
    {
        return $this->hasMany(ErrorLog::class);
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    /** @param Builder<static> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ProductStatus::Pending);
    }

    /** @param Builder<static> $query */
    public function scopeProcessing(Builder $query): void
    {
        $query->where('status', ProductStatus::Processing);
    }

    /** @param Builder<static> $query */
    public function scopeSuccessful(Builder $query): void
    {
        $query->where('status', ProductStatus::Successful);
    }

    /** @param Builder<static> $query */
    public function scopeFailed(Builder $query): void
    {
        $query->where('status', ProductStatus::Failed);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Returns true when this product has been successfully pushed to Shopify
     * and a Shopify GID has been stored.
     *
     * Accessed as: $product->is_synced
     *
     * @return Attribute<bool, never>
     */
    protected function isSynced(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->shopify_product_id !== null
                && $this->status === ProductStatus::Successful,
        );
    }

    /**
     * Returns true when compare_at_price is greater than price,
     * indicating the product has a visible discount in the storefront.
     *
     * Accessed as: $product->has_discount
     *
     * @return Attribute<bool, never>
     */
    protected function hasDiscount(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->compare_at_price !== null
                && (float) $this->compare_at_price > (float) $this->price,
        );
    }
}
