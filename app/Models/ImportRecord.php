<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportAction;
use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single Shopify API attempt for one product row.
 *
 * A product row may have multiple ImportRecords if the job is retried —
 * each retry creates a new record rather than overwriting the previous one,
 * preserving the full retry history for audit and debugging.
 *
 * @property int                      $id
 * @property int                      $upload_id
 * @property int                      $product_id
 * @property int                      $row_number
 * @property ImportAction|null        $action
 * @property ProductStatus            $status
 * @property array<string, mixed>|null $shopify_response
 * @property string|null              $error_message
 * @property \Carbon\Carbon|null       $started_at
 * @property \Carbon\Carbon|null       $completed_at
 * @property \Carbon\Carbon            $created_at
 * @property \Carbon\Carbon            $updated_at
 *
 * Computed accessors:
 * @property-read int|null $processing_duration_seconds
 *
 * @method static Builder<static> successful()
 * @method static Builder<static> failed()
 * @method static Builder<static> pending()
 * @method static Builder<static> processing()
 */
class ImportRecord extends Model
{
    /** @use HasFactory<\Database\Factories\ImportRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'product_id',
        'row_number',
        'action',
        'status',
        'shopify_response',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            // Reuses ProductStatus — import records share the identical state machine.
            'status'           => ProductStatus::class,
            // Nullable: action is only known once the worker begins processing.
            'action'           => ImportAction::class,
            // Raw Shopify GraphQL response stored as an associative array.
            'shopify_response' => 'array',
            'row_number'       => 'integer',
            'started_at'       => 'datetime',
            'completed_at'     => 'datetime',
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

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
     * Wall-clock seconds taken to process this row against the Shopify API.
     * Returns null when either timestamp is missing (e.g. row is still pending).
     * Useful for per-row API latency reporting.
     *
     * Accessed as: $record->processing_duration_seconds
     *
     * @return Attribute<int|null, never>
     */
    protected function processingDurationSeconds(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => ($this->started_at && $this->completed_at)
                ? (int) $this->started_at->diffInSeconds($this->completed_at)
                : null,
        );
    }
}
