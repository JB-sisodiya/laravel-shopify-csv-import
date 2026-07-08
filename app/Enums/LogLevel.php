<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * PSR-3 compliant log severity levels for structured error logging.
 *
 * levels are available for future use without a schema change.
 */
enum LogLevel: string
{
    case Debug     = 'debug';
    case Info      = 'info';
    case Notice    = 'notice';
    case Warning   = 'warning';
    case Error     = 'error';
    case Critical  = 'critical';
    case Alert     = 'alert';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match($this) {
            self::Debug     => 'Debug',
            self::Info      => 'Info',
            self::Notice    => 'Notice',
            self::Warning   => 'Warning',
            self::Error     => 'Error',
            self::Critical  => 'Critical',
            self::Alert     => 'Alert',
            self::Emergency => 'Emergency',
        };
    }

    /**
     * Used to filter the error log table for alerts that require attention.
     */
    public function isActionable(): bool
    {
        return match($this) {
            self::Error, self::Critical, self::Alert, self::Emergency => true,
            default => false,
        };
    }

    public function severity(): int
    {
        return match($this) {
            self::Debug     => 0,
            self::Info      => 1,
            self::Notice    => 2,
            self::Warning   => 3,
            self::Error     => 4,
            self::Critical  => 5,
            self::Alert     => 6,
            self::Emergency => 7,
        };
    }
}
