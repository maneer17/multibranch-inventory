<?php

namespace App\Enums;

enum MovementType: string
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


}
