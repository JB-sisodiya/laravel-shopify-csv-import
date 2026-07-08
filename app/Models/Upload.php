<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UploadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename',
        'stored_filename',
        'file_path',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'status',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [
            'status'          => UploadStatus::class,
            'total_rows'      => 'integer',
            'processed_rows'  => 'integer',
            'successful_rows' => 'integer',
            'failed_rows'     => 'integer',
            'started_at'      => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
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
        $query->where('status', UploadStatus::Pending);
    }

    /** @param Builder<static> $query */
    public function scopeProcessing(Builder $query): void
    {
        $query->where('status', UploadStatus::Processing);
    }

    /** @param Builder<static> $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', UploadStatus::Completed);
    }

    /** @param Builder<static> $query */
    public function scopeFailed(Builder $query): void
    {
        $query->where('status', UploadStatus::Failed);
    }

    protected function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->total_rows > 0
                ? round(($this->processed_rows / $this->total_rows) * 100, 2)
                : 0.0,
        );
    }

 
    protected function isComplete(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status->isTerminal(),
        );
    }
}
