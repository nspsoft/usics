<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\AppSetting;
use App\Services\WhatsappBotService;
use Illuminate\Support\Facades\Log;

class CheckLowStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-low-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for products that have fallen below their reorder point and send WhatsApp alerts.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsappBotService $whatsapp)
    {
        if (!AppSetting::get('notify_low_stock', true)) {
            $this->info('Low stock notifications are disabled in System Preferences.');
            return Command::SUCCESS;
        }

        $this->info('Starting Low Stock Check...');

        // Get products that are below reorder point AND stock managed and active
        // using the scope we saw in Product model: scopeLowStock()
        $lowStockProducts = Product::active()
            ->stockManaged()
            ->with('unit')
            ->lowStock()
            ->get();

        if ($lowStockProducts->isEmpty()) {
            $this->info('No low stock items found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$lowStockProducts->count()} low stock items.");

        // Get target phone number (Manager or Purchasing logic)
        $targetPhone = AppSetting::get('whatsapp_admin_phone');

        if (!$targetPhone) {
            $this->error('No admin phone number configured (whatsapp_admin_phone). Skipping notification.');
            Log::warning('Low stock check ran but no whatsapp_admin_phone is configured to receive the alert.');
            return Command::FAILURE;
        }

        // Build the message
        $message = "⚠️ *LOW STOCK ALERT* ⚠️\n\n";
        $message .= "Berikut adalah daftar barang yang stoknya sudah berada di bawah Reorder Point (Minimum Batas Aman):\n\n";

        foreach ($lowStockProducts as $product) {
            $unit = $product->unit->name ?? 'Pcs';
            $available = $product->available_stock;
            $rop = $product->reorder_point;
            
            $message .= "• *{$product->sku}*\n";
            $message .= "  {$product->name}\n";
            $message .= "  Stok: *{$available}* {$unit} (ROP: {$rop})\n\n";
        }

        $message .= "Harap segera buat *Purchase Request (PR)* atau *Production Request* untuk mengisi kembali stok barang-barang ini.\n\n";
        $companyName = \App\Models\AppSetting::get('company_full_name') ?: 'USICS ERP';
        $message .= "_Sistem Otomatis {$companyName}_";

        // Send via WhatsApp
        try {
            $success = $whatsapp->sendNotification($targetPhone, $message);
            
            if ($success) {
                $this->info("WhatsApp alert sent successfully to $targetPhone.");
                Log::info('Low stock WhatsApp alert sent', ['count' => $lowStockProducts->count()]);
            } else {
                $this->error("Failed to send WhatsApp alert via provider.");
            }
        } catch (\Exception $e) {
            $this->error("Exception while sending WhatsApp: " . $e->getMessage());
            Log::error("Low stock WhatsApp error: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
