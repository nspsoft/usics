<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsappBotService
{
    protected $gateway;
    protected GeminiService $gemini;
    protected QuotationBotService $quotationService;
    protected string $provider;
    protected static ?bool $hasIsReadColumn = null;

    public function __construct(GeminiService $gemini, QuotationBotService $quotationService)
    {
        $this->gemini = $gemini;
        $this->quotationService = $quotationService;
        
        // Get provider from settings
        $this->provider = AppSetting::get('whatsapp_provider', 'fonnte');
        
        // Initialize the appropriate gateway service
        $this->gateway = match ($this->provider) {
            'wablas' => app(WablasService::class),
            default => app(FonnteService::class),
        };
    }

    /**
     * Handle incoming WhatsApp message
     */
    public function handleIncomingMessage(string $phone, string $message, ?string $pushName = null): string
    {
        // Find customer by phone
        $customer = $this->findCustomerByPhone($phone);
        
        // Log incoming message
        $this->logMessage($phone, $message, 'incoming', $customer?->id);

        // Fetch last 8 messages for conversation context (memory)
        $conversationHistory = WhatsappMessage::where('phone', $phone)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'role' => $m->direction === 'incoming' ? 'customer' : 'bot',
                'message' => $m->message,
            ])
            ->values()
            ->toArray();

        $customerContext = [
            'name' => $customer ? $customer->name : ($pushName ?? 'Guest'),
            'is_registered' => (bool) $customer,
            'has_orders' => $customer ? $customer->salesOrders()->exists() : false,
        ];

        // Analyze intent using Gemini (with conversation history)
        $intent = $this->gemini->analyzeCustomerIntent($message, $customerContext, $conversationHistory);

        Log::info('WhatsApp Bot Intent', ['phone' => $phone, 'intent' => $intent]);

        // Handle based on intent
        $response = match ($intent['intent'] ?? 'unknown') {
            'order_status' => $this->handleOrderStatus($customer, $intent['parameters'] ?? []),
            'invoice_check' => $this->handleInvoiceCheck($customer),
            'product_catalog' => $this->handleProductCatalog($intent['parameters'] ?? [], $phone),
            'request_quotation' => $this->handleRequestQuotation($customer, $intent['parameters'] ?? []),
            'greeting' => $this->handleGreeting($customer, $message),
            'casual_chat' => $this->handleCasualChat($customer, $message, $conversationHistory),
            'faq' => $this->handleFAQ($message, $conversationHistory),
            default => $this->handleUnknown($customer, $message, $conversationHistory),
        };

        // Log outgoing message
        $this->logMessage($phone, $response, 'outgoing', $customer?->id, $intent['intent'] ?? null);

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
     * Find customer by phone number
     */
    protected function findCustomerByPhone(string $phone): ?Customer
    {
        // Normalize phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Try different formats
        $variations = [
            $phone,
            '0' . substr($phone, 2), // 62xxx -> 0xxx
            substr($phone, 2), // Remove 62
        ];

        return Customer::where(function ($q) use ($variations) {
            foreach ($variations as $p) {
                $q->orWhere('phone', 'like', "%{$p}%");
            }
        })->first();
    }

    /**
     * Handle order status inquiry
     */
    protected function handleOrderStatus(?Customer $customer, array $params): string
    {
        if (!$customer) {
            return "Maaf, nomor Anda belum terdaftar sebagai customer kami.\n\nUntuk mendaftar, silakan hubungi sales kami di 021-xxx-xxxx.";
        }

        $orderNumber = $params['order_number'] ?? null;

        if ($orderNumber) {
            // Find specific order
            $order = SalesOrder::where('so_number', 'like', "%{$orderNumber}%")
                ->where('customer_id', $customer->id)
                ->with(['deliveryOrders'])
                ->first();

            if (!$order) {
                return "Pesanan dengan nomor *{$orderNumber}* tidak ditemukan.\n\nPastikan nomor pesanan sudah benar.";
            }

            return $this->formatOrderStatus($order);
        }

        // Get recent orders
        $orders = SalesOrder::where('customer_id', $customer->id)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        if ($orders->isEmpty()) {
            return "Anda tidak memiliki pesanan aktif saat ini.\n\nUntuk melakukan pemesanan baru, silakan hubungi sales kami.";
        }

        $response = "📦 *Pesanan Aktif Anda*\n\n";
        foreach ($orders as $order) {
            $status = $this->translateStatus($order->status);
            $response .= "• *{$order->so_number}*\n";
            $response .= "  Status: {$status}\n";
            $response .= "  Tanggal: " . $order->order_date->format('d M Y') . "\n\n";
        }

        $response .= "Ketik nomor SO untuk detail lengkap.";
        return $response;
    }

    /**
     * Handle invoice/payment inquiry
     */
    protected function handleInvoiceCheck(?Customer $customer): string
    {
        if (!$customer) {
            return "Maaf, nomor Anda belum terdaftar sebagai customer kami.\n\nUntuk informasi tagihan, silakan hubungi bagian finance kami.";
        }

        $invoices = SalesInvoice::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->get();

        if ($invoices->isEmpty()) {
            return "✅ Tidak ada tagihan yang tertunggak.\n\nTerima kasih telah menjadi pelanggan setia kami!";
        }

        $total = $invoices->sum('total_amount');
        $response = "📄 *Invoice Outstanding*\n\n";

        foreach ($invoices as $invoice) {
            $dueDate = $invoice->due_date->format('d M Y');
            $isOverdue = $invoice->due_date->isPast();
            $status = $isOverdue ? "⚠️ JATUH TEMPO" : "📅 {$dueDate}";
            
            $response .= "• *{$invoice->invoice_number}*\n";
            $response .= "  Rp " . number_format($invoice->total_amount, 0, ',', '.') . "\n";
            $response .= "  {$status}\n\n";
        }

        $response .= "━━━━━━━━━━━━━━━\n";
        $response .= "*Total: Rp " . number_format($total, 0, ',', '.') . "*\n\n";
        $response .= "Untuk pembayaran, transfer ke:\n";
        $response .= "BCA 123-456-789\n";
        $response .= "a.n. PT SPINDO Tbk";

        return $response;
    }

    /**
     * Handle product catalog inquiry
     */
    protected function handleProductCatalog(array $params, string $phone = ''): string
    {
        $search = $params['product_name'] ?? null;

        if (!$search) {
            return "🔧 *Katalog Produk*\n\nSilakan sebutkan nama produk yang Anda cari.\nContoh: \"Harga pipa 2 inch\" atau \"Stok besi beton\"";
        }

        $products = \App\Models\Product::where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            return "Maaf, produk *\"{$search}\"* tidak ditemukan.\n\nSilakan coba kata kunci lain atau hubungi sales kami.";
        }

        $response = "🔧 *Hasil Pencarian: \"{$search}\"*\n\n";

        foreach ($products as $product) {
            // Send image if available
            if ($product->image && $phone) {
                try {
                    $imageUrl = $product->image;
                    if (!str_starts_with($imageUrl, 'http')) {
                        $imageUrl = config('app.url') . '/storage/' . $imageUrl;
                    }
                    
                    $this->gateway->sendImage($phone, $imageUrl, $product->name);
                } catch (\Exception $e) {
                    Log::error("Failed to send product image: " . $e->getMessage());
                }
            }

            $stock = $product->available_stock; 
            $price = $product->selling_price > 0 
                ? "Rp " . number_format($product->selling_price, 0, ',', '.') 
                : "Call for Price";
            $unit = $product->unit->name ?? 'Pcs';

            $response .= "• *{$product->name}* ({$product->sku})\n";
            $response .= "  Harga: {$price}\n";
            $response .= "  Stok: " . number_format($stock) . " {$unit}\n\n";
        }

        $response .= "Ketik \"Minta penawaran [Nama Produk]\" untuk request harga khusus.";
        return $response;
    }

    /**
     * Handle request quotation
     */
    protected function handleRequestQuotation(?Customer $customer, array $params): string
    {
        $productName = $params['product_name'] ?? null;
        $quantity = $params['quantity'] ?? 1;

        if (!$productName) {
            return "Untuk meminta penawaran, silakan sebutkan nama produk dan jumlahnya.\nContoh: \"Minta penawaran Pipa Besi 100 batang\"";
        }

        if (!$customer) {
            return "Maaf, untuk pembuatan penawaran resmi, nomor Anda harus terdaftar terlebih dahulu.\nSilakan hubungi Sales kami.";
        }

        // 1. Generate PDF Draft
        try {
            $pdfUrl = $this->quotationService->generateQuotation($customer, [
                ['product_name' => $productName, 'quantity' => $quantity]
            ]);

            if (empty($pdfUrl)) {
                return "Maaf, produk *\"{$productName}\"* tidak ditemukan di database kami untuk dibuatkan penawaran otomatis.";
            }

            // 2. Send PDF to Customer
            if ($this->provider === 'wablas') {
                $this->gateway->sendFile($customer->phone, $pdfUrl, "Draft Penawaran - {$productName}");
            } else {
                // Fonnte fallback (send as link or file if supported)
                // For now, send link
                return "✅ Draft Penawaran Anda siap!\n\nSilakan unduh di sini:\n{$pdfUrl}\n\n*Catatan:* Ini adalah draft otomatis. Hubungi sales untuk negosiasi lebih lanjut.";
            }

            return "✅ Draft Penawaran untuk *{$productName}* ({$quantity}) telah kami kirimkan dalam format PDF. Silakan cek dokumen di atas 👆";

        } catch (\Exception $e) {
            Log::error("Failed to generate quotation PDF: " . $e->getMessage());
            return "Maaf, terjadi kesalahan saat membuat penawaran otomatis. Tim sales kami akan segera menghubungi Anda manual.";
        }
    }

    /**
     * Handle greeting
     */
    protected function handleGreeting(?Customer $customer, string $message): string
    {
        // Use Gemini to generate a friendly, human-like greeting in Indonesian
        return $this->gemini->generateFAQResponse("Sapa saya dengan ramah sebagai CS PT SPINDO. Pesan saya: \"{$message}\"");
    }

    /**
     * Handle casual chat (small talk)
     */
    protected function handleCasualChat(?Customer $customer, string $message, array $conversationHistory = []): string
    {
        return $this->gemini->generateFAQResponse($message, $conversationHistory);
    }

    /**
     * Handle FAQ
     */
    protected function handleFAQ(string $message, array $conversationHistory = []): string
    {
        // Use Gemini to generate FAQ response
        return $this->gemini->generateFAQResponse($message, $conversationHistory);
    }

    /**
     * Handle unknown intent
     */
    protected function handleUnknown(?Customer $customer, string $message, array $conversationHistory = []): string
    {
        // Try to let AI handle it as a general query first
        $aiResponse = $this->gemini->generateFAQResponse($message, $conversationHistory);
        
        if (str_contains($aiResponse, "Maaf") && str_contains($aiResponse, "tidak mengerti")) {
            return "Maaf, saya tidak mengerti pertanyaan Anda.\n\nSaya bisa membantu untuk:\n• Cek status pesanan (ketik: status SO-xxx)\n• Cek tagihan (ketik: cek tagihan)\n• Jam operasional dan info umum\n\nAtau hubungi CS kami di 021-xxx-xxxx.";
        }
        
        return $aiResponse;
    }

    /**
     * Format order status response
     */
    protected function formatOrderStatus(SalesOrder $order): string
    {
        $status = $this->translateStatus($order->status);
        $items = $order->items->take(10)->map(fn($i) => "• {$i->product->name} (" . number_format($i->quantity) . " " . ($i->unit->name ?? $i->unit_id) . ")")->join("\n");
        
        $response = "📦 *Status Pesanan*\n\n";
        $response .= "*{$order->so_number}*\n";
        $response .= "Status: {$status}\n";
        $response .= "Tanggal: " . $order->order_date->format('d M Y') . "\n\n";
        $response .= "*Item:*\n{$items}\n";

        if ($order->deliveryOrders->isNotEmpty()) {
            $response .= "\n*Info Pengiriman:*\n";
            foreach ($order->deliveryOrders as $do) {
                $statusStr = $this->translateStatus($do->status);
                $trackUrl = route('sales.deliveries.public-validate', $do->public_uuid ?: $do->id);
                
                $response .= "🚚 *{$do->do_number}*\n";
                $response .= "   Status: {$statusStr}\n";
                $response .= "   Tgl: " . ($do->delivery_date?->format('d M Y') ?? '-') . "\n";
                $response .= "   Lacak: {$trackUrl}\n\n";
            }
        }

        return $response;
    }

    /**
     * Translate status to Indonesian
     */
    protected function translateStatus(string $status): string
    {
        return match ($status) {
            'draft' => '📝 Draft',
            'confirmed' => '✅ Dikonfirmasi',
            'processing' => '🔄 Diproses',
            'shipped' => '🚚 Dikirim',
            'delivered' => '📬 Terkirim',
            'completed' => '✅ Selesai',
            'cancelled' => '❌ Dibatalkan',
            default => ucfirst($status),
        };
    }

    /**
     * Log message to database
     */
    protected function logMessage(string $phone, string $message, string $direction, ?int $customerId = null, ?string $intent = null): void
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
                'customer_id' => $customerId,
                'direction' => $direction,
                'message' => $message,
                'intent' => $intent,
            ];

            if (self::$hasIsReadColumn) {
                $payload['is_read'] = $direction !== 'incoming';
            }

            WhatsappMessage::create($payload);

            if ($direction === 'incoming') {
                Cache::forget('whatsapp_unread_count');
            }
        } catch (\Exception $e) {
            Log::error('Failed to log WhatsApp message: ' . $e->getMessage());
        }
    }
}
