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

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Requested => in_array($next, [self::Approved, self::Rejected, self::Cancelled], true),
            self::Approved => in_array($next, [self::InTransit, self::Cancelled], true),
            // Only one way forward once stock has left the source branch.
            // No cancellation here, by design.
            self::InTransit => $next === self::Received,
            // Final states: nothing can follow them.
            self::Rejected, self::Received, self::Cancelled => false,
        };
    }
}
