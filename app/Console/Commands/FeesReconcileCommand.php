<?php

namespace App\Console\Commands;

use App\Models\Fee;
use App\Support\Audit;
use App\Support\FeeBalance;
use Illuminate\Console\Command;

class FeesReconcileCommand extends Command
{
    protected $signature = 'app:fees-reconcile {--dry-run : Report mismatches without changing them}';

    protected $description = 'Recalculate fee amount_paid and status values from payment rows';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $checked = 0;
        $mismatches = 0;
        $repaired = 0;

        Fee::with('payments')->orderBy('id')->chunkById(200, function ($fees) use ($dryRun, &$checked, &$mismatches, &$repaired) {
            foreach ($fees as $fee) {
                $checked++;
                $netPaid = FeeBalance::netPaid($fee);
                $status = FeeBalance::status($fee, $netPaid);
                $amountMismatch = bccomp((string) $fee->amount_paid, $netPaid, 2) !== 0;
                $statusMismatch = $fee->status !== $status;

                if (! $amountMismatch && ! $statusMismatch) {
                    continue;
                }

                $mismatches++;

                if ($dryRun) {
                    $this->line("Fee #{$fee->id}: amount_paid {$fee->amount_paid} -> {$netPaid}, status {$fee->status} -> {$status}");

                    continue;
                }

                $oldAmount = $fee->amount_paid;
                $oldStatus = $fee->status;

                $fee->update([
                    'amount_paid' => $netPaid,
                    'status' => $status,
                ]);

                Audit::record('fees.reconciled', 'Fee balance corrected by reconcile command.', [
                    'fee_id' => $fee->id,
                    'old_amount_paid' => $oldAmount,
                    'new_amount_paid' => $netPaid,
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ]);

                $repaired++;
            }
        });

        $this->info("Checked {$checked} fees. Mismatches: {$mismatches}. Repaired: {$repaired}.");

        return self::SUCCESS;
    }
}
