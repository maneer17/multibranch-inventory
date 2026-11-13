<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileStockCommand extends Command
{
    protected $signature = 'inventory:reconcile';
    protected $description = 'Compare branch_stock.quantity against the ledger SUM(stock_movements.quantity) and report mismatches';

    public function handle(): int
    {
        $this->info('Scanning for mismatches between cached stock and the ledger...');
        $this->newLine();

        $mismatches = DB::select("
            SELECT
                bs.id AS branch_stock_id,
                b.name AS branch_name,
                p.name AS product_name,
                bs.quantity AS cached_quantity,
                COALESCE(SUM(sm.quantity), 0) AS ledger_sum,
                bs.quantity - COALESCE(SUM(sm.quantity), 0) AS difference
            FROM branch_stock bs
            JOIN branches b ON b.id = bs.branch_id
            JOIN products p ON p.id = bs.product_id
            LEFT JOIN stock_movements sm
                ON sm.branch_id = bs.branch_id
                AND sm.product_id = bs.product_id
            GROUP BY bs.id, b.name, p.name, bs.quantity
            HAVING bs.quantity != COALESCE(SUM(sm.quantity), 0)
        ");

        if (empty($mismatches)) {
            $this->info('✓ All clear — every cached quantity matches the ledger exactly.');
            return self::SUCCESS;
        }

        $this->warn(count($mismatches) . ' mismatch(es) found:');
        $this->newLine();

        $this->table(
            ['Branch', 'Product', 'Cached', 'Ledger', 'Difference'],
            collect($mismatches)->map(fn ($row) => [
                $row->branch_name,
                $row->product_name,
                $row->cached_quantity,
                $row->ledger_sum,
                $row->difference,
            ])->all(),
        );

        $this->newLine();

        if (! $this->confirm('Do you want to fix these by updating cached quantities to match the ledger?')) {
            $this->info('No changes made.');
            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($mismatches as $row) {
            DB::table('branch_stock')
                ->where('id', $row->branch_stock_id)
                ->update(['quantity' => $row->ledger_sum]);

            $fixed++;

            $this->line("  Fixed: {$row->branch_name} / {$row->product_name} — {$row->cached_quantity} → {$row->ledger_sum}");
        }

        $this->newLine();
        $this->info("✓ {$fixed} row(s) corrected.");

        return self::SUCCESS;
    }
}
