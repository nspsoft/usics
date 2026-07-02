<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\Rfq;
use App\Models\PurchaseInvoice;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PurchasingWhatsappBotService
{
    protected $gateway;
    protected GeminiService $gemini;
    protected string $provider;
    protected static ?bool $hasIsReadColumn = null;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
        
        // Get provider from settings
        $this->provider = AppSetting::get('purchasing_whatsapp_provider', 'fonnte');
        
        // Initialize the appropriate gateway service with dynamic credentials
        if ($this->provider === 'wablas') {
            $token = AppSetting::get('purchasing_wablas_api_token', '');
            $url = AppSetting::get('purchasing_wablas_server_url', 'https://pati.wablas.com');
            $this->gateway = app(WablasService::class)->setCredentials($token, $url);
        } else {
            $token = AppSetting::get('purchasing_fonnte_api_token', '');
            $this->gateway = app(FonnteService::class)->setCredentials($token);
        }
    }

    /**
     * Handle incoming WhatsApp message for Purchasing Bot
     */
    public function handleIncomingMessage(string $phone, string $message, ?string $pushName = null): string
    {
        // Find supplier by phone
        $supplier = $this->findSupplierByPhone($phone);
        
        // Log incoming message
        $incomingMsg = $this->logMessage($phone, $message, 'incoming', $supplier?->id);

        // Fetch last 8 messages for conversation context (memory)
        $conversationHistory = WhatsappMessage::where('phone', $phone)
            ->where('module', 'purchasing')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'role' => $m->direction === 'incoming' ? 'supplier' : 'bot',
                'message' => $m->message,
            ])
            ->values()
            ->toArray();

        $supplierContext = [
            'name' => $supplier ? $supplier->name : ($pushName ?? 'Supplier Guest'),
            'is_registered' => (bool) $supplier,
            'has_pos' => $supplier ? $supplier->purchaseOrders()->exists() : false,
        ];

        // Analyze intent using Gemini (with conversation history)
        $intent = $this->gemini->analyzeSupplierIntent($message, $supplierContext, $conversationHistory);

        // Update incoming message with analyzed intent and sentiment
        if ($incomingMsg) {
            $incomingMsg->update([
                'intent' => $intent['intent'] ?? 'unknown',
                'metadata' => array_merge($incomingMsg->metadata ?? [], [
                    'sentiment' => $intent['sentiment'] ?? 'neutral',
                    'confidence' => $intent['confidence'] ?? null,
                    'parameters' => $intent['parameters'] ?? []
                ])
            ]);
        }

        Log::info('Purchasing WhatsApp Bot Intent', ['phone' => $phone, 'intent' => $intent]);

        // Handle based on intent
        $response = match ($intent['intent'] ?? 'unknown') {
            'po_status' => $this->handlePoStatus($supplier, $intent['parameters'] ?? []),
            'grn_status' => $this->handleGrnStatus($supplier, $intent['parameters'] ?? []),
            'rfq_status' => $this->handleRfqStatus($supplier, $intent['parameters'] ?? []),
            'supplier_invoice' => $this->handleSupplierInvoice($supplier),
            'greeting' => $this->handleGreeting($supplier, $message),
            'casual_chat' => $this->handleCasualChat($supplier, $message, $conversationHistory),
            'faq' => $this->handleFAQ($message, $conversationHistory),
            default => $this->handleUnknown($supplier, $message, $conversationHistory),
        };

        // Log outgoing message
        $this->logMessage($phone, $response, 'outgoing', $supplier?->id, $intent['intent'] ?? null);

        // Send response via selected gateway
        $this->gateway->sendMessage($phone, $response);

        return $response;
    }

    /**
     * Send a proactive notification (System Initiated)
     */
    public function sendNotification(string $phone, string $message): bool
    {
        // Log notification intent
        $this->logMessage($phone, $message, 'notification');

        // Send via gateway
        $result = $this->gateway->sendMessage($phone, $message);
        
        return $result['success'] ?? false;
    }

    /**
     * Find supplier by phone number (checks both Supplier & SupplierContact)
     */
    protected function findSupplierByPhone(string $phone): ?Supplier
    {
        // Normalize phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Try different formats
        $variations = [
            $phone,
            '0' . substr($phone, 2), // 62xxx -> 0xxx
            substr($phone, 2), // Remove 62
        ];

        // Search in Supplier phone
        $supplier = Supplier::where(function ($q) use ($variations) {
            foreach ($variations as $p) {
                if (empty($p)) continue;
                $q->orWhere('phone', 'like', "%{$p}%");
            }
        })->first();

        if ($supplier) {
            return $supplier;
        }

        // Search in SupplierContact phone
        $contact = SupplierContact::where(function ($q) use ($variations) {
            foreach ($variations as $p) {
                if (empty($p)) continue;
                $q->orWhere('phone', 'like', "%{$p}%");
            }
        })->first();

        return $contact?->supplier;
    }

    /**
     * Handle Purchase Order status inquiry
     */
    protected function handlePoStatus(?Supplier $supplier, array $params): string
    {
        if (!$supplier) {
            return "Maaf, nomor Anda belum terdaftar sebagai supplier kami.\n\nUntuk mendaftar atau mendaftarkan kontak Anda, silakan hubungi bagian Purchasing kami.";
        }

        $poNumber = $params['po_number'] ?? null;

        if ($poNumber) {
            $order = PurchaseOrder::where('po_number', 'like', "%{$poNumber}%")
                ->where('supplier_id', $supplier->id)
                ->first();

            if (!$order) {
                return "Purchase Order dengan nomor *{$poNumber}* tidak ditemukan untuk supplier Anda.\n\nPastikan nomor PO sudah benar.";
            }

            return $this->formatPoStatus($order);
        }

        // Get recent POs
        $orders = PurchaseOrder::where('supplier_id', $supplier->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        if ($orders->isEmpty()) {
            return "Tidak ada Purchase Order (PO) aktif yang tercatat untuk Anda saat ini.";
        }

        $response = "📦 *Purchase Order Aktif Anda*\n\n";
        foreach ($orders as $order) {
            $status = $this->translatePoStatus($order->status);
            $response .= "• *{$order->po_number}*\n";
            $response .= "  Status: {$status}\n";
            $response .= "  Tanggal PO: " . $order->order_date->format('d M Y') . "\n";
            $response .= "  Estimasi Kirim: " . ($order->expected_date?->format('d M Y') ?: '-') . "\n\n";
        }

        $response .= "Ketik nomor PO (contoh: *PO-xxxx*) untuk detail lengkap.";
        return $response;
    }

    /**
     * Handle Goods Receipt Note status inquiry
     */
    protected function handleGrnStatus(?Supplier $supplier, array $params): string
    {
        if (!$supplier) {
            return "Maaf, nomor Anda belum terdaftar sebagai supplier kami.";
        }

        $grnNumber = $params['grn_number'] ?? null;
        $poNumber = $params['po_number'] ?? null;

        if ($grnNumber) {
            $gr = GoodsReceipt::where('grn_number', 'like', "%{$grnNumber}%")
                ->where('supplier_id', $supplier->id)
                ->first();

            if (!$gr) {
                return "Goods Receipt (GRN) dengan nomor *{$grnNumber}* tidak ditemukan.";
            }

            return $this->formatGrnStatus($gr);
        }

        if ($poNumber) {
            $grs = GoodsReceipt::whereHas('purchaseOrder', function($q) use ($poNumber) {
                    $q->where('po_number', 'like', "%{$poNumber}%");
                })
                ->where('supplier_id', $supplier->id)
                ->orderByDesc('created_at')
                ->get();
        } else {
            $grs = GoodsReceipt::where('supplier_id', $supplier->id)
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();
        }

        if ($grs->isEmpty()) {
            return "Tidak ditemukan data penerimaan barang (GRN) untuk supplier Anda.";
        }

        $response = "🚚 *Penerimaan Barang (GRN) Terbaru*\n\n";
        foreach ($grs as $gr) {
            $status = $this->translateGrnStatus($gr->status);
            $response .= "• *{$gr->grn_number}*\n";
            $response .= "  PO: " . ($gr->purchaseOrder?->po_number ?? '-') . "\n";
            $response .= "  Status: {$status}\n";
            $response .= "  Tgl Terima: " . $gr->receipt_date->format('d M Y') . "\n\n";
        }

        $response .= "Ketik nomor GRN (contoh: *GRN-xxxx*) untuk detail lengkap.";
        return $response;
    }

    /**
     * Handle RFQ status inquiry
     */
    protected function handleRfqStatus(?Supplier $supplier, array $params): string
    {
        if (!$supplier) {
            return "Maaf, nomor Anda belum terdaftar sebagai supplier kami.";
        }

        $rfqNumber = $params['rfq_number'] ?? null;

        if ($rfqNumber) {
            $rfq = Rfq::where('rfq_number', 'like', "%{$rfqNumber}%")
                ->whereHas('targetSuppliers', function($q) use ($supplier) {
                    $q->where('supplier_id', $supplier->id);
                })
                ->first();

            if (!$rfq) {
                return "RFQ dengan nomor *{$rfqNumber}* tidak ditemukan untuk supplier Anda.";
            }

            return $this->formatRfqStatus($rfq, $supplier);
        }

        // Get open RFQs
        $rfqs = Rfq::whereHas('targetSuppliers', function($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            })
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        if ($rfqs->isEmpty()) {
            return "Saat ini tidak ada Request for Quotation (RFQ) aktif yang ditujukan untuk Anda.";
        }

        $response = "📨 *Request for Quotation (RFQ) Aktif*\n\n";
        foreach ($rfqs as $rfq) {
            $pivot = $rfq->targetSuppliers()->where('supplier_id', $supplier->id)->first()?->pivot;
            $pivotStatus = $this->translateRfqPivotStatus($pivot?->status);
            
            $response .= "• *{$rfq->rfq_number}* - {$rfq->title}\n";
            $response .= "  Batas Waktu: " . ($rfq->deadline?->format('d M Y H:i') ?: '-') . "\n";
            $response .= "  Status Anda: {$pivotStatus}\n\n";
        }

        $response .= "Ketik nomor RFQ (contoh: *RFQ-xxxx*) untuk melihat detail item.";
        return $response;
    }

    /**
     * Handle Supplier Invoice status inquiry
     */
    protected function handleSupplierInvoice(?Supplier $supplier): string
    {
        if (!$supplier) {
            return "Maaf, nomor Anda belum terdaftar sebagai supplier kami.";
        }

        $invoices = PurchaseInvoice::where('supplier_id', $supplier->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date')
            ->get();

        if ($invoices->isEmpty()) {
            return "✅ Tidak ada invoice outstanding (belum terbayar) yang tercatat.\n\nTerima kasih atas kerja sama Anda!";
        }

        $totalDue = $invoices->sum(fn($i) => $i->amount_due);
        $response = "📄 *Outstanding Invoices Supplier*\n\n";

        foreach ($invoices as $invoice) {
            $dueDate = $invoice->due_date?->format('d M Y') ?: '-';
            $isOverdue = $invoice->due_date?->isPast() ?? false;
            $status = $isOverdue ? "⚠️ JATUH TEMPO" : "📅 {$dueDate}";
            $paidPercent = $invoice->total_amount > 0 ? round(($invoice->paid_amount / $invoice->total_amount) * 100, 1) : 0;
            $piStatus = $invoice->status === 'partial' ? "DIBAYAR SEBAGIAN ({$paidPercent}%)" : "BELUM DIBAYAR";

            $response .= "• *{$invoice->invoice_number}*\n";
            $response .= "  Total: Rp " . number_format($invoice->total_amount, 0, ',', '.') . "\n";
            $response .= "  Sisa Tagihan: Rp " . number_format($invoice->amount_due, 0, ',', '.') . "\n";
            $response .= "  Status: {$piStatus}\n";
            $response .= "  Jatuh Tempo: {$status}\n\n";
        }

        $response .= "━━━━━━━━━━━━━━━\n";
        $response .= "*Total Sisa Tagihan: Rp " . number_format($totalDue, 0, ',', '.') . "*\n\n";
        $response .= "Proses pembayaran dilakukan sesuai termin pembayaran (TOP) yang berlaku.";

        return $response;
    }

    /**
     * Handle greeting
     */
    protected function handleGreeting(?Supplier $supplier, string $message): string
    {
        return $this->gemini->generateSupplierFAQResponse("Sapa supplier dengan ramah sebagai CS Purchasing PT SPINDO. Pesan mereka: \"{$message}\"");
    }

    /**
     * Handle casual chat (small talk)
     */
    protected function handleCasualChat(?Supplier $supplier, string $message, array $conversationHistory = []): string
    {
        return $this->gemini->generateSupplierFAQResponse($message, $conversationHistory);
    }

    /**
     * Handle FAQ
     */
    protected function handleFAQ(string $message, array $conversationHistory = []): string
    {
        return $this->gemini->generateSupplierFAQResponse($message, $conversationHistory);
    }

    /**
     * Handle unknown intent
     */
    protected function handleUnknown(?Supplier $supplier, string $message, array $conversationHistory = []): string
    {
        $aiResponse = $this->gemini->generateSupplierFAQResponse($message, $conversationHistory);
        
        if (str_contains($aiResponse, "Maaf") && str_contains($aiResponse, "tidak mengerti")) {
            return "Maaf, saya tidak mengerti pertanyaan Anda.\n\nSaya asisten virtual Purchasing bisa membantu:\n• Cek status PO (ketik: status PO-xxx)\n• Cek status barang (ketik: cek GRN-xxx atau kiriman PO-xxx)\n• Cek status RFQ (ketik: cek RFQ)\n• Cek status pembayaran invoice (ketik: cek invoice)\n• Jam bongkar muat & info umum\n\nAtau hubungi tim Purchasing kami secara langsung.";
        }
        
        return $aiResponse;
    }

    /**
     * Format PO status detail response
     */
    protected function formatPoStatus(PurchaseOrder $order): string
    {
        $status = $this->translatePoStatus($order->status);
        $items = $order->items->take(10)->map(fn($i) => "• {$i->product->name} (" . number_format($i->qty) . " " . ($i->unit->name ?? $i->unit_id) . " | Diterima: " . number_format($i->qty_received) . ")")->join("\n");
        
        $response = "📦 *Status Purchase Order*\n\n";
        $response .= "*{$order->po_number}*\n";
        $response .= "Status: {$status}\n";
        $response .= "Tanggal PO: " . $order->order_date->format('d M Y') . "\n";
        $response .= "Estimasi Kirim: " . ($order->expected_date?->format('d M Y') ?: '-') . "\n\n";
        $response .= "*Item Pesanan:*\n{$items}\n";

        $order->load('goodsReceipts');
        if ($order->goodsReceipts->isNotEmpty()) {
            $response .= "\n*Penerimaan Barang (GRN):*\n";
            foreach ($order->goodsReceipts as $gr) {
                $grStatus = $this->translateGrnStatus($gr->status);
                $response .= "   *{$gr->grn_number}* (" . $grStatus . ")\n";
                $response .= "   Tgl Terima: " . ($gr->receipt_date?->format('d M Y') ?? '-') . "\n";
                if ($gr->driver_name) {
                    $response .= "   Driver: {$gr->driver_name} (" . ($gr->truck_number ?? '-') . ")\n";
                }
                $response .= "\n";
            }
        }

        return $response;
    }

    /**
     * Format GRN detail response
     */
    protected function formatGrnStatus(GoodsReceipt $gr): string
    {
        $status = $this->translateGrnStatus($gr->status);
        $items = $gr->items->take(10)->map(fn($i) => "• {$i->product->name} (" . number_format($i->qty_received) . " " . ($i->unit->name ?? $i->unit_id) . ")")->join("\n");

        $response = "🚚 *Detail Goods Receipt (GRN)*\n\n";
        $response .= "*{$gr->grn_number}*\n";
        $response .= "PO Ref: " . ($gr->purchaseOrder?->po_number ?? '-') . "\n";
        $response .= "Status: {$status}\n";
        $response .= "Tanggal Terima: " . $gr->receipt_date->format('d M Y') . "\n";
        $response .= "Surat Jalan Supplier: " . ($gr->delivery_note_number ?? '-') . "\n";
        if ($gr->driver_name) {
            $response .= "Driver: {$gr->driver_name} (" . ($gr->truck_number ?? '-') . ")\n";
        }
        $response .= "\n*Item yang Diterima:*\n{$items}\n";

        return $response;
    }

    /**
     * Format RFQ detail response
     */
    protected function formatRfqStatus(Rfq $rfq, Supplier $supplier): string
    {
        $pivot = $rfq->targetSuppliers()->where('supplier_id', $supplier->id)->first()?->pivot;
        $pivotStatus = $this->translateRfqPivotStatus($pivot?->status);
        $items = $rfq->items->take(10)->map(fn($i) => "• {$i->product_name} (" . number_format($i->quantity) . " " . ($i->unit->name ?? $i->unit_id) . ")")->join("\n");

        $response = "📨 *Detail Request for Quotation (RFQ)*\n\n";
        $response .= "*{$rfq->rfq_number}*\n";
        $response .= "Judul: {$rfq->title}\n";
        $response .= "Deskripsi: " . ($rfq->description ?: '-') . "\n";
        $response .= "Batas Waktu: " . ($rfq->deadline?->format('d M Y H:i') ?: '-') . "\n";
        $response .= "Status RFQ: " . ($rfq->status === 'open' ? '🟢 Terbuka' : '🔴 Ditutup') . "\n";
        $response .= "Status Respon Anda: {$pivotStatus}\n\n";
        $response .= "*Item yang Diminta:*\n{$items}\n\n";
        $response .= "Silakan kirimkan penawaran harga terbaik Anda melalui portal supplier atau email ke purchasing kami.";

        return $response;
    }

    protected function translatePoStatus(string $status): string
    {
        return match ($status) {
            'draft' => '📝 Draft',
            'submitted' => '⏳ Diajukan',
            'approved' => '✅ Disetujui',
            'ordered' => '📦 Dipesan',
            'acknowledged' => '🤝 Konfirmasi Supplier',
            'rejected' => '❌ Ditolak',
            'partial' => '🔄 Diterima Sebagian',
            'received' => '📬 Diterima Lengkap',
            'cancelled' => '🚫 Dibatalkan',
            default => ucfirst($status),
        };
    }

    protected function translateGrnStatus(string $status): string
    {
        return match ($status) {
            'draft' => '📝 Draft',
            'dispatched' => '🚚 Dikirim',
            'received' => '📦 Diterima Gudang',
            'inspected' => '🔍 Diinspeksi QC',
            'completed' => '✅ Selesai (Masuk Stok)',
            default => ucfirst($status),
        };
    }

    protected function translateRfqPivotStatus(?string $status): string
    {
        return match ($status) {
            'sent' => '📨 Dikirim',
            'viewed' => '👁️ Dilihat',
            'responded' => '✅ Sudah Merespon',
            default => 'Belum merespon',
        };
    }

    /**
     * Log message to database
     */
    protected function logMessage(string $phone, string $message, string $direction, ?int $supplierId = null, ?string $intent = null, ?array $metadata = null): ?WhatsappMessage
    {
        try {
            if ($direction === 'notification') {
                $direction = 'outgoing';
                $intent = $intent ?: 'notification';
            }

            if (!in_array($direction, ['incoming', 'outgoing'], true)) {
                $direction = 'incoming';
            }

            if (self::$hasIsReadColumn === null) {
                self::$hasIsReadColumn = Schema::hasColumn('whatsapp_messages', 'is_read');
            }

            $payload = [
                'phone' => $phone,
                'supplier_id' => $supplierId,
                'direction' => $direction,
                'message' => $message,
                'intent' => $intent,
                'module' => 'purchasing',
                'metadata' => $metadata,
            ];

            if (self::$hasIsReadColumn) {
                $payload['is_read'] = $direction !== 'incoming';
            }

            $msg = WhatsappMessage::create($payload);

            if ($direction === 'incoming') {
                Cache::forget('purchasing_whatsapp_unread_count');
            }

            return $msg;
        } catch (\Exception $e) {
            Log::error('Failed to log Purchasing WhatsApp message: ' . $e->getMessage());
            return null;
        }
    }
}
