<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappContactLabel;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Services\FonnteService;
use App\Services\WablasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PurchasingWhatsappCenterController extends Controller
{
    protected static ?bool $hasIsReadColumn = null;

    protected function hasIsReadColumn(): bool
    {
        if (self::$hasIsReadColumn !== null) {
            return self::$hasIsReadColumn;
        }

        return self::$hasIsReadColumn = Schema::hasColumn('whatsapp_messages', 'is_read');
    }

    /**
     * Display the Purchasing WhatsApp Center
     */
    public function index()
    {
        // Get unique contacts sorted by latest message for purchasing module
        $contacts = WhatsappMessage::module('purchasing')
            ->select('phone', 'supplier_id')
            ->selectRaw('MAX(created_at) as last_activity')
            ->selectRaw('(SELECT message FROM whatsapp_messages wm WHERE wm.phone = whatsapp_messages.phone AND wm.module = "purchasing" ORDER BY created_at DESC LIMIT 1) as last_message')
            ->selectRaw('(SELECT intent FROM whatsapp_messages wm WHERE wm.phone = whatsapp_messages.phone AND wm.module = "purchasing" ORDER BY created_at DESC LIMIT 1) as last_intent')
            ->groupBy('phone', 'supplier_id')
            ->orderByDesc('last_activity')
            ->with('supplier:id,name,contact_person')
            ->get();

        $totalUnread = 0;
        $templates = collect();
        $labelPresets = [];

        if ($this->hasIsReadColumn()) {
            $unreadCounts = WhatsappMessage::module('purchasing')
                ->unread()
                ->selectRaw('phone, COUNT(*) as unread_count')
                ->groupBy('phone')
                ->pluck('unread_count', 'phone');

            $contacts->transform(function ($contact) use ($unreadCounts) {
                $contact->unread_count = $unreadCounts[$contact->phone] ?? 0;
                return $contact;
            });

            $totalUnread = Cache::remember('purchasing_whatsapp_unread_count', 10, function () {
                return WhatsappMessage::module('purchasing')->unread()->count();
            });
        } else {
            $contacts->transform(function ($contact) {
                $contact->unread_count = 0;
                return $contact;
            });
        }

        try {
            // Get labels grouped by phone
            $allLabels = WhatsappContactLabel::all()->groupBy('phone');
            $contacts->transform(function ($contact) use ($allLabels) {
                $contact->labels = $allLabels[$contact->phone] ?? collect();
                return $contact;
            });
            $labelPresets = WhatsappContactLabel::presets();
        } catch (\Exception $e) {
            $contacts->transform(function ($contact) {
                $contact->labels = collect();
                return $contact;
            });
        }

        try {
            $templates = WhatsappTemplate::active()->orderBy('sort_order')->get();
        } catch (\Exception $e) {
            // Templates table may not exist yet
        }

        // Suppliers for New Chat picker
        $suppliers = Supplier::active()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', 'name', 'contact_person', 'phone')
            ->orderBy('name')
            ->get();

        return Inertia::render('Purchasing/Whatsapp/Index', [
            'contacts' => $contacts,
            'totalUnread' => $totalUnread,
            'templates' => $templates,
            'labelPresets' => $labelPresets,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Get messages for a specific phone number (Chat History)
     */
    public function history(string $phone)
    {
        // Mark all incoming messages for this phone as read
        if ($this->hasIsReadColumn()) {
            WhatsappMessage::module('purchasing')
                ->where('phone', $phone)
                ->where('direction', 'incoming')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            Cache::forget('purchasing_whatsapp_unread_count');
        }

        $messages = WhatsappMessage::module('purchasing')
            ->where('phone', $phone)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a manual message to a supplier
     */
    public function send(Request $request, FonnteService $fonnte, WablasService $wablas)
    {
        $request->validate([
            'phone' => 'required',
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // Max 10MB
        ]);

        $phone = $request->phone;
        $message = $request->message ?? '';
        $provider = AppSetting::get('purchasing_whatsapp_provider') ?: 'fonnte';
        
        // Configure dynamic credentials
        if ($provider === 'wablas') {
            $token = AppSetting::get('purchasing_wablas_api_token', '');
            $url = AppSetting::get('purchasing_wablas_server_url', 'https://pati.wablas.com');
            $activeService = $wablas->setCredentials($token, $url);
        } else {
            $token = AppSetting::get('purchasing_fonnte_api_token', '');
            $activeService = $fonnte->setCredentials($token);
        }

        $result = ['success' => false, 'error' => 'No content to send'];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $path = $file->store('whatsapp-attachments', 'public');
            $url = asset('storage/' . $path);

            $attachmentMeta = [
                'url' => $url,
                'mime' => $mime,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];

            if (str_starts_with($mime, 'image/')) {
                $result = $activeService->sendImage($phone, $url, $message);
                $attachmentMeta['type'] = 'image';
            } else {
                $result = $activeService->sendFile($phone, $url, $message);
                $attachmentMeta['type'] = 'document';
            }
            
            $logMessage = $message ?: "[File: {$file->getClientOriginalName()}]";
        } elseif (!empty($message)) {
            $result = $activeService->sendMessage($phone, $message);
            $logMessage = $message;
        }

        // Always log the attempt if we had content
        if (isset($logMessage)) {
            // Find supplier by phone number variations
            $supplierId = null;
            $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
            $variations = [
                $normalizedPhone,
                '0' . substr($normalizedPhone, 2),
                substr($normalizedPhone, 2),
            ];

            $supplier = Supplier::where(function ($q) use ($variations) {
                foreach ($variations as $p) {
                    if (empty($p)) continue;
                    $q->orWhere('phone', 'like', "%{$p}%");
                }
            })->first();

            if ($supplier) {
                $supplierId = $supplier->id;
            } else {
                $contact = SupplierContact::where(function ($q) use ($variations) {
                    foreach ($variations as $p) {
                        if (empty($p)) continue;
                        $q->orWhere('phone', 'like', "%{$p}%");
                    }
                })->first();
                $supplierId = $contact?->supplier_id;
            }

            WhatsappMessage::create([
                'phone' => $phone,
                'direction' => 'outgoing',
                'message' => $logMessage,
                'intent' => 'manual_reply',
                'is_read' => true,
                'module' => 'purchasing',
                'metadata' => array_merge($attachmentMeta ?? [], [
                    'delivery_success' => $result['success'],
                    'delivery_error' => $result['error'] ?? null
                ]),
                'supplier_id' => $supplierId,
            ]);
        }

        if ($result['success']) {
            return back()->with('success', 'Message sent successfully');
        }

        $errorMessage = $result['error'] ?? 'Unknown error';
        
        if (str_contains(request()->getHost(), '.test') || str_contains(request()->getHost(), 'localhost')) {
            $errorMessage .= ". Note: File sending often fails on local environments karena provider (Wablas/Fonnte) tidak bisa mengakses URL lokal. Fitur ini akan lancar setelah di-deploy ke server online.";
        }

        return back()->with('error', 'Failed to send: ' . $errorMessage);
    }

    /**
     * Delete chat history for a specific phone number
     */
    public function destroy(string $phone)
    {
        WhatsappMessage::module('purchasing')->where('phone', $phone)->delete();
        WhatsappContactLabel::where('phone', $phone)->delete();

        return back()->with('success', 'Chat history deleted successfully.');
    }

    /**
     * Get unread count for purchasing sidebar indicator
     */
    public function unreadCount()
    {
        if (!$this->hasIsReadColumn()) {
            return response()->json([
                'total' => 0,
            ]);
        }

        $total = Cache::remember('purchasing_whatsapp_unread_count', 10, function () {
            return WhatsappMessage::module('purchasing')->unread()->count();
        });

        return response()->json([
            'total' => $total,
        ]);
    }

    /**
     * Add a label to a contact.
     */
    public function addLabel(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'label' => 'required|string|max:50',
            'color' => 'required|string|max:20',
        ]);

        WhatsappContactLabel::firstOrCreate(
            ['phone' => $data['phone'], 'label' => $data['label']],
            ['color' => $data['color']]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Remove a label from a contact.
     */
    public function removeLabel(WhatsappContactLabel $label)
    {
        $label->delete();
        return response()->json(['success' => true]);
    }
}
