<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use App\Services\FonnteService;
use Illuminate\Http\Request;
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
    public function send(Request $request, FonnteService $fonnte)
    {
        $request->validate([
            'phone' => 'required',
            'message' => 'required|string',
        ]);

        $phone = $request->phone;
        $message = $request->message;

        // Send via Fonnte
        $result = $fonnte->sendMessage($phone, $message);

        if ($result['success']) {
            // Log manually sent message
            WhatsappMessage::create([
                'phone' => $phone,
                'direction' => 'outgoing',
                'message' => $message,
                'intent' => 'manual_reply', 
                // We should also link customer_id if possible, but for now it's okay
                'customer_id' => \App\Models\Customer::where('phone', $phone)->orWhere('mobile', $phone)->value('id'),
            ]);

            return back()->with('success', 'Message sent successfully');
        }

            return back()->with('error', 'Failed to send message: ' . ($result['error'] ?? 'Unknown error'));
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
