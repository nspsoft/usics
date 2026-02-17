<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\WhatsappMessage;
use App\Services\FonnteService;
use App\Services\WablasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class WhatsappCenterController extends Controller
{
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
            ->with('customer:id,name,contact_person,profile_photo_path') // Eager load customer details
            ->get();

        return Inertia::render('Sales/Whatsapp/Index', [
            'contacts' => $contacts
        ]);
    }

    /**
     * Get messages for a specific phone number (Chat History).
     */
    public function history(string $phone)
    {
        $messages = WhatsappMessage::where('phone', $phone)
            ->orderBy('created_at', 'asc') // Oldest first for chat bubble
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
            $errorMessage .= ". Note: File sending often fails on local environments لأن provider (Wablas/Fonnte) tidak bisa mengakses URL lokal Bapak. Fitur ini akan lancar setelah di-deploy ke server online.";
        }

        return back()->with('error', 'Failed to send: ' . $errorMessage);
    }

    /**
     * Delete chat history for a specific phone number.
     */
    public function destroy(string $phone)
    {
        WhatsappMessage::where('phone', $phone)->delete();

        return back()->with('success', 'Chat history deleted successfully.');
    }
}
