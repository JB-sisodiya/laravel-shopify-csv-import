<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LogLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Structured application-level error log for the import pipeline.
 *
 * Distinct from ImportRecord: ImportRecord tracks per-row Shopify API outcomes,
 * while ErrorLog captures exceptions, parse failures, and system-level errors
 * at any point in the pipeline. Both FKs are nullable so errors can be logged
 * before a product row or even an upload record exists.
 *
 * @property int                      $id
 * @property int|null                 $upload_id
 * @property int|null                 $product_id
 * @property LogLevel                 $level
 * @property string|null              $source
 * @property string                   $message
 * @property string|null              $exception
 * @property array<string, mixed>|null $payload
 * @property \Carbon\Carbon            $created_at
 * @property \Carbon\Carbon            $updated_at
 *
 * @method static Builder<static> ofLevel(LogLevel $level)
 * @method static Builder<static> actionable()
 */
class ErrorLog extends Model
{
    /** @use HasFactory<\Database\Factories\ErrorLogFactory> */
    use HasFactory;

    protected $fillable = [
        'upload_id',
        'product_id',
        'level',
        'source',
        'message',
        'exception',
        'payload',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'level'   => LogLevel::class,
            // Arbitrary structured context stored as an associative array.
            'payload' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The upload associated with this error, or null for system-level errors
     * that occurred before or outside the scope of a specific upload.
     *
     * @return BelongsTo<Upload, $this>
     */
    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    /**
     * The product associated with this error, or null when the error occurred
     * before a product row was created (e.g. CSV parse failure).
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // -------------------------------------------------------------------------
    // Query scopes
    // -------------------------------------------------------------------------

    /**
     * Filter by a specific PSR-3 log level.
     *
     * @param Builder<static> $query
     */
    public function scopeOfLevel(Builder $query, LogLevel $level): void
    {
        $query->where('level', $level);
    }

    /**
     * Filter to errors that require developer attention:
     * Error, Critical, Alert, and Emergency levels.
     *
     * @param Builder<static> $query
     */
    public function scopeActionable(Builder $query): void
    {
        $query->whereIn('level', [
            LogLevel::Error,
            LogLevel::Critical,
            LogLevel::Alert,
            LogLevel::Emergency,
        ]);
    }
}
