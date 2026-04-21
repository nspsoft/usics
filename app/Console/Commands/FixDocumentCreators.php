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
    protected $description = 'Automatically update Purchase Orders and Sales Orders created by Admin to Agus Suprianto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $adminId = 2; // User: Admin
        $targetId = 11; // User: Agus Suprianto (agus)

        // Verify users exist
        $admin = User::find($adminId);
        $target = User::find($targetId);

        if (!$target) {
            $this->error("Target user (ID {$targetId}) not found.");
            return 1;
        }

        // Update Purchase Orders
        $poCount = PurchaseOrder::where('created_by', $adminId)->update(['created_by' => $targetId]);
        $this->info("Updated {$poCount} Purchase Orders.");

        // Update Sales Orders
        $soCount = SalesOrder::where('created_by', $adminId)->update(['created_by' => $targetId]);
        $this->info("Updated {$soCount} Sales Orders.");

        return 0;
    }
}
