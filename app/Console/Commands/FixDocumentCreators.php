<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;

class FixDocumentCreators extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchase-order:fix-creator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically update Purchase Orders and Sales Orders created by Admin/Santi to Agus Suprianto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define emails for safer identification across environments
        $adminEmail = 'admin@usc-indonesia.co.id';
        $santiEmail = 'santi@usc-indonesia.co.id';
        $targetEmail = 'agus@usc-indonesia.co.id';

        $admin = User::where('email', $adminEmail)->first();
        $santi = User::where('email', $santiEmail)->first();
        $target = User::where('email', $targetEmail)->first();

        if (!$target) {
            $this->error("Target user ({$targetEmail}) not found. Skipping update.");
            return 1;
        }

        $sourceIds = array_filter([
            $admin ? $admin->id : null,
            $santi ? $santi->id : null,
        ]);

        if (empty($sourceIds)) {
            $this->warn("No source users (Admin/Santi) found. Nothing to update.");
            return 0;
        }

        // Update Purchase Orders
        $poCount = PurchaseOrder::whereIn('created_by', $sourceIds)->update(['created_by' => $target->id]);
        $this->info("Updated {$poCount} Purchase Orders to {$target->name}.");

        // Update Sales Orders
        $soCount = SalesOrder::whereIn('created_by', $sourceIds)->update(['created_by' => $target->id]);
        $this->info("Updated {$soCount} Sales Orders to {$target->name}.");

        return 0;
    }
}
