<?php

namespace App\Console\Commands;

use App\Models\SocietyDatabase;
use App\Models\Tenant\RazorpayOrder;
use App\Services\RazorpayService;
use App\Services\TenantService;
use Illuminate\Console\Command;

/**
 * Safety net for online bill payments across every society: asks Razorpay
 * about each recent order that isn't marked paid and records any payment
 * it actually captured - so money taken is never left off a bill because
 * the app closed or lost network before it could confirm the payment.
 * Scheduled every five minutes (routes/console.php).
 */
class ReconcileRazorpayPayments extends Command
{
    protected $signature = 'payments:reconcile {--hours=72 : Only check orders created within this many hours}';

    protected $description = 'Record Razorpay payments that were taken but never confirmed by the app';

    public function handle(TenantService $tenants, RazorpayService $razorpay): int
    {
        $recorded = 0;

        foreach (SocietyDatabase::active()->get() as $societyDatabase) {
            $tenants->setTenant($societyDatabase->society_id);

            // A society without its own Razorpay keys takes no online payments.
            if (!$razorpay->isConfigured()) {
                continue;
            }

            RazorpayOrder::query()
                ->where('status', '!=', 'paid')
                ->where('created_at', '>=', now()->subHours((int) $this->option('hours')))
                ->get()
                ->each(function (RazorpayOrder $order) use ($razorpay, $societyDatabase, &$recorded) {
                    if ($razorpay->reconcileOrder($order)) {
                        $recorded++;
                        $this->line("Society #{$societyDatabase->society_id}: recorded payment for order {$order->razorpay_order_id} (bill #{$order->bill_id}).");
                    }
                });
        }

        $this->info("Recorded {$recorded} payment(s).");

        return self::SUCCESS;
    }
}
