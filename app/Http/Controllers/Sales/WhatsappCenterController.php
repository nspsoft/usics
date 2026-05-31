<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappContactLabel;
use App\Models\Customer;
use App\Services\FonnteService;
use App\Services\WablasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WhatsappCenterController extends Controller
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
     * Display the WhatsApp Center (Chat List & Interface).
     */
    public function index()
    {
        // Get unique contacts (phone numbers) sorted by latest message
        $contacts = WhatsappMessage::select('phone', 'customer_id')
            ->selectRaw('MAX(created_at) as last_activity')
            ->selectRaw('(SELECT message FROM whatsapp_messages wm WHERE wm.phone = whatsapp_messages.phone ORDER BY created_at DESC LIMIT 1) as last_message')
            ->selectRaw('(SELECT intent FROM whatsapp_messages wm WHERE wm.phone = whatsapp_messages.phone ORDER BY created_at DESC LIMIT 1) as last_intent')
            ->groupBy('phone', 'customer_id')
            ->orderByDesc('last_activity')
            ->with('customer:id,name,contact_person,profile_photo_path')
            ->get();

        $totalUnread = 0;
        $templates = collect();
        $labelPresets = [];

        if ($this->hasIsReadColumn()) {
            $unreadCounts = WhatsappMessage::unread()
                ->selectRaw('phone, COUNT(*) as unread_count')
                ->groupBy('phone')
                ->pluck('unread_count', 'phone');

            $contacts->transform(function ($contact) use ($unreadCounts) {
                $contact->unread_count = $unreadCounts[$contact->phone] ?? 0;
                return $contact;
            });

            $totalUnread = Cache::remember('whatsapp_unread_count', 10, function () {
                return WhatsappMessage::unread()->count();
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
            // Labels table may not exist yet
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

        // Customers for New Chat picker
        $customers = Customer::active()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', 'name', 'contact_person', 'phone', 'profile_photo_path')
            ->orderBy('name')
            ->get();

        return Inertia::render('Sales/Whatsapp/Index', [
            'contacts' => $contacts,
            'totalUnread' => $totalUnread,
            'templates' => $templates,
            'labelPresets' => $labelPresets,
            'customers' => $customers,
        ]);
    }

    /**
     * Get messages for a specific phone number (Chat History).
     * Also marks incoming messages as read.
     */
    public function history(string $phone)
    {
        // Mark all incoming messages for this phone as read
        if ($this->hasIsReadColumn()) {
            WhatsappMessage::where('phone', $phone)
                ->where('direction', 'incoming')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            Cache::forget('whatsapp_unread_count');
        }

        $messages = WhatsappMessage::where('phone', $phone)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a manual message to a customer.
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
        $provider = AppSetting::get('whatsapp_provider') ?: 'fonnte';
        $activeService = ($provider === 'wablas') ? $wablas : $fonnte;

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
            WhatsappMessage::create([
                'phone' => $phone,
                'direction' => 'outgoing',
                'message' => $logMessage,
                'intent' => 'manual_reply',
                'is_read' => true,
                'metadata' => array_merge($attachmentMeta ?? [], [
                    'delivery_success' => $result['success'],
                    'delivery_error' => $result['error'] ?? null
                ]),
                'customer_id' => \App\Models\Customer::where('phone', $phone)->orWhere('mobile', $phone)->value('id'),
            ]);
        }

        if ($result['success']) {
            return back()->with('success', 'Message sent successfully');
        }

        $errorMessage = $result['error'] ?? 'Unknown error';
        
        // Add helpful hint for local environments
        if (str_contains(request()->getHost(), '.test') || str_contains(request()->getHost(), 'localhost')) {
            $errorMessage .= ". Note: File sending often fails on local environments karena provider (Wablas/Fonnte) tidak bisa mengakses URL lokal. Fitur ini akan lancar setelah di-deploy ke server online.";
        }

        return back()->with('error', 'Failed to send: ' . $errorMessage);
    }

    /**
     * Delete chat history for a specific phone number.
     */
    public function destroy(string $phone)
    {
        WhatsappMessage::where('phone', $phone)->delete();
        WhatsappContactLabel::where('phone', $phone)->delete();

        return back()->with('success', 'Chat history deleted successfully.');
    }

    // ──── Template Management ────

    /**
     * Store a new template.
     */
    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'body' => 'required|string',
        ]);

        WhatsappTemplate::create($data);

        return back()->with('success', 'Template created successfully.');
    }

    /**
     * Update a template.
     */
    public function updateTemplate(Request $request, WhatsappTemplate $template)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'body' => 'required|string',
        ]);

        $template->update($data);

        return back()->with('success', 'Template updated successfully.');
    }

    /**
     * Delete a template.
     */
    public function destroyTemplate(WhatsappTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'Template deleted successfully.');
    }

    // ──── Label Management ────

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

    /**
     * Get unread count (for AJAX polling from sidebar).
     */
    public function unreadCount()
    {
        if (!$this->hasIsReadColumn()) {
            return response()->json([
                'total' => 0,
            ]);
        }

        $total = Cache::remember('whatsapp_unread_count', 10, function () {
            return WhatsappMessage::unread()->count();
        });

        return response()->json([
            'total' => $total,
        ]);
    }
}
