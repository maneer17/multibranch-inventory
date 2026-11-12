<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Rejected = 'rejected';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Requested',
            self::Approved => 'Approved',
            self::InTransit => 'In Transit',
            self::Rejected => 'Rejected',
            self::Received => 'Received',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'warning',
            self::Approved => 'info',
            self::InTransit => 'primary',
            self::Rejected => 'danger',
            self::Received => 'success',
            self::Cancelled => 'gray',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Requested => in_array($next, [self::Approved, self::Rejected, self::Cancelled], true),
            self::Approved => in_array($next, [self::InTransit, self::Cancelled], true),
            self::InTransit => $next === self::Received,
            self::Rejected, self::Received, self::Cancelled => false,
        };
    }
}
