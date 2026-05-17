<?php

declare(strict_types=1);

namespace App\Enum;

enum BalanceType: string {
    case Actual = 'actual';
    case Incoming = 'incoming';

    /**
     * @note Instantiates 'now' directly.
     * If reused in loops, extract this value beforehand to prevent time drift.
     */
    public function toMaxDate(): \DateTimeImmutable
    {
        return match ($this) {
            self::Actual => new \DateTimeImmutable('now'),
            self::Incoming => new \DateTimeImmutable('+14days'),
        };
    }
}
