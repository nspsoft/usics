<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Services\PurchasingWhatsappBotService;
use Illuminate\Support\Facades\Log;

class PurchaseOrderObserver
{
    protected PurchasingWhatsappBotService $whatsappService;

    public function __construct(PurchasingWhatsappBotService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $order): void
    {
        if ($order->isDirty('status') && $order->status === 'ordered') {
            $this->sendPoNotification($order);
        }
    }

    /**
     * Send WhatsApp notification to supplier
     */
    protected function sendPoNotification(PurchaseOrder $order): void
    {
        $order->load(['supplier', 'supplier.contacts', 'items.product', 'items.unit']);

        if (!$order->supplier) {
            return;
        }

        $phone = null;
        if (!empty($order->supplier->phone)) {
            $phone = $order->supplier->phone;
        } else {
            $contact = $order->supplier->contacts()
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->first();
            if ($contact) {
                $phone = $contact->phone;
            }
        }

        if (!$phone) {
            Log::warning("Skipped WhatsApp PO notification: No phone number found for supplier {$order->supplier->name} (PO: {$order->po_number})");
            return;
        }

        // Normalize phone number to 62xxx
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62') && strlen($phone) > 0) {
            $phone = '62' . $phone;
        }

        $supplierName = $order->supplier->name;
        $poNumber = $order->po_number;
        $orderDate = $order->order_date ? $order->order_date->format('d M Y') : date('d M Y');
        $expectedDate = $order->expected_date ? $order->expected_date->format('d M Y') : '-';
        $notes = $order->notes ? "\nCatatan: {$order->notes}" : "";

        // Generate list of items (up to 5 items)
        $itemsCount = $order->items->count();
        $itemsList = $order->items->take(5)->map(function ($item) {
            $unit = $item->unit->name ?? $item->unit->symbol ?? 'pcs';
            return "• {$item->product->name} (" . number_format($item->qty) . " {$unit})";
        })->join("\n");

        if ($itemsCount > 5) {
            $itemsList .= "\n• ... dan " . ($itemsCount - 5) . " item lainnya.";
        }

        $message = "📦 *Pemberitahuan Purchase Order (PO) Baru*\n\n" .
                   "Halo *{$supplierName}*,\n\n" .
                   "Kami dari Departemen Purchasing PT SPINDO menginformasikan bahwa kami telah menerbitkan PO baru:\n\n" .
                   "• *Nomor PO:* {$poNumber}\n" .
                   "• *Tanggal PO:* {$orderDate}\n" .
                   "• *Estimasi Kirim:* {$expectedDate}\n" .
                   "{$notes}\n\n" .
                   "*Rincian Item Pesanan:*\n" .
                   "{$itemsList}\n\n" .
                   "Mohon segera memeriksa detail pesanan di atas dan mengonfirmasi kesiapan pengiriman Anda dengan membalas pesan ini atau melalui portal vendor.\n\n" .
                   "Terima kasih atas kerja samanya.";

        try {
            $this->whatsappService->sendNotification($phone, $message);
            Log::info("Sent WhatsApp notification for Purchase Order {$poNumber} status: ordered");
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp PO notification: " . $e->getMessage());
        }
    }
}
