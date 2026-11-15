<?php

use App\Enums\TransferStatus;

// ── Valid transitions ────────────────────────────────────────────

it('allows Requested → Approved', fn () =>
    expect(TransferStatus::Requested->canTransitionTo(TransferStatus::Approved))->toBeTrue()
);

it('allows Requested → Rejected', fn () =>
    expect(TransferStatus::Requested->canTransitionTo(TransferStatus::Rejected))->toBeTrue()
);

it('allows Requested → Cancelled', fn () =>
    expect(TransferStatus::Requested->canTransitionTo(TransferStatus::Cancelled))->toBeTrue()
);

it('allows Approved → InTransit', fn () =>
    expect(TransferStatus::Approved->canTransitionTo(TransferStatus::InTransit))->toBeTrue()
);

it('allows Approved → Cancelled', fn () =>
    expect(TransferStatus::Approved->canTransitionTo(TransferStatus::Cancelled))->toBeTrue()
);

it('allows InTransit → Received', fn () =>
    expect(TransferStatus::InTransit->canTransitionTo(TransferStatus::Received))->toBeTrue()
);

// ── Invalid transitions ─────────────────────────────────────────

it('blocks Requested → InTransit (must be approved first)', fn () =>
    expect(TransferStatus::Requested->canTransitionTo(TransferStatus::InTransit))->toBeFalse()
);

it('blocks Requested → Received', fn () =>
    expect(TransferStatus::Requested->canTransitionTo(TransferStatus::Received))->toBeFalse()
);

it('blocks Approved → Approved (no self-transition)', fn () =>
    expect(TransferStatus::Approved->canTransitionTo(TransferStatus::Approved))->toBeFalse()
);

it('blocks Approved → Received (must ship first)', fn () =>
    expect(TransferStatus::Approved->canTransitionTo(TransferStatus::Received))->toBeFalse()
);

it('blocks InTransit → Cancelled (too late)', fn () =>
    expect(TransferStatus::InTransit->canTransitionTo(TransferStatus::Cancelled))->toBeFalse()
);

// ── Terminal states block all transitions ────────────────────────

it('blocks any transition from Rejected', function () {
    foreach (TransferStatus::cases() as $target) {
        expect(TransferStatus::Rejected->canTransitionTo($target))->toBeFalse();
    }
});

it('blocks any transition from Received', function () {
    foreach (TransferStatus::cases() as $target) {
        expect(TransferStatus::Received->canTransitionTo($target))->toBeFalse();
    }
});

it('blocks any transition from Cancelled', function () {
    foreach (TransferStatus::cases() as $target) {
        expect(TransferStatus::Cancelled->canTransitionTo($target))->toBeFalse();
    }
});
