<?php

declare(strict_types=1);

namespace App\Enums;

enum UploadStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pending',
            self::Processing => 'Processing',
            self::Completed  => 'Completed',
            self::Failed     => 'Failed',
        };
    }

    
    public function isTerminal(): bool
    {
        return match($this) {
            self::Completed, self::Failed => true,
            default                       => false,
        };
    }

    /** Returns true while the batch is still being actively worked on. */
    public function isActive(): bool
    {
        return $this === self::Processing;
    }
}
