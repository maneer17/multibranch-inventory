<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MovementType: string implements HasLabel, HasColor
{
    case Sale = 'sale';
    case PurchaseReceipt = 'purchase_receipt';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case InitialStockUpload = 'initial_stock_upload';

    public function isInbound(): bool
    {
        return match ($this) {
            self::PurchaseReceipt, self::TransferIn, self::InitialStockUpload, self::AdjustmentIn => true,
            self::Sale, self::TransferOut, self::AdjustmentOut => false,
        };
    }

    public function isOutbound(): bool
    {
        return ! $this->isInbound();
    }

    // Automatically handles the human-readable text in the badge
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Sale => 'Sale',
            self::PurchaseReceipt => 'Purchase Receipt',
            self::TransferIn => 'Transfer In',
            self::TransferOut => 'Transfer Out',
            self::AdjustmentIn => 'Adjustment In',
            self::AdjustmentOut => 'Adjustment Out',
            self::InitialStockUpload => 'Initial Stock Upload',
        };
    }

    // Automatically handles the fitted colors for the badge
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PurchaseReceipt => 'success', // Green: New stock arrived
            self::InitialStockUpload => 'gray', // Gray: System setup baseline
            self::TransferIn => 'info',         // Blue: Internal movement coming in
            self::AdjustmentIn => 'primary',    // Indigo: Manual stock increase

            self::Sale => 'warning',            // Orange: Customer fulfillment outbound
            self::TransferOut => 'warning',     // Orange: Stock leaving location
            self::AdjustmentOut => 'danger',    // Red: Stock deduction/shrinkage/loss
        };
    }
}
