<?php

namespace App\Observers;

use App\Models\SalesOrder;
use App\Services\WhatsappBotService;
use Illuminate\Support\Facades\Log;

class OrderStatusObserver
{
    protected $whatsappService;

    public function __construct(WhatsappBotService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the SalesOrder "updated" event.
     */
    public function updated(SalesOrder $order): void
    {
        if ($order->isDirty('status')) {
            $newStatus = $order->status;
            $customer = $order->customer;

            if ($customer && $customer->phone) {
                // Map status to friendly message
                $message = $this->getMessageForStatus($order, $newStatus);

                if ($message) {
                    try {
                        // Send notification via WhatsApp Service
                        // We need to expose a method in WhatsappBotService or use Gateway directly
                        // Using Gateway directly via Service for now to reuse logging
                        $this->whatsappService->sendNotification($customer->phone, $message);
                        
                        Log::info("Sent WhatsApp notification for Order {$order->so_number} status: {$newStatus}");
                    } catch (\Exception $e) {
                        Log::error("Failed to send WhatsApp notification: " . $e->getMessage());
                    }
                }
            }
        }
    }

    protected function getMessageForStatus(SalesOrder $order, string $status): ?string
    {
        return match ($status) {
            'confirmed' => "✅ Pesanan *{$order->so_number}* telah Dikonfirmasi!\n\nKami sedang memproses pesanan Anda. Terima kasih!",
            'processing' => "⚙️ Pesanan *{$order->so_number}* sedang Diproses.\n\nTim gudang kami sedang menyiapkan barang Anda.",
            'shipped' => "🚚 Kabar Gembira! Pesanan *{$order->so_number}* sedang DIKIRIM.\n\nMohon pastikan ada penerima di lokasi pengiriman.",
            'delivered' => "📦 Pesanan *{$order->so_number}* telah DITERIMA.\n\nTerima kasih telah berbelanja di PT SPINDO. Ditunggu pesanan berikutnya! 🙏",
            'cancelled' => "❌ Pesanan *{$order->so_number}* telah Dibatalkan.\n\nSilakan hubungi kami jika ini kekeliruan.",
            default => null,
        };
    }
}
