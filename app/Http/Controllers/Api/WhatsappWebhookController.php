<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhatsappBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    protected WhatsappBotService $botService;

    public function __construct(WhatsappBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Handle incoming webhook from Fonnte
     */
    public function handle(Request $request)
    {
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/whatsapp.log'),
        ])->info('Webhook Payload:', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'json' => $request->json()->all(),
        ]);
        // Log::info('Webhook Payload', $request->all());

        // Check for Wablas format (phone) or Fonnte format (sender)
        $sender = $request->input('sender') ?: $request->input('phone');
        $message = $request->input('message');
        
        if (!$sender || !$message) {
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $isGroup = str_contains($sender, '@g.us');

        // Ignore group messages
        if ($isGroup) {
            return response()->json(['status' => 'ignored', 'reason' => 'group_message']);
        }

        // Extract phone number (remove @s.whatsapp.net if present)
        $phone = str_replace(['@s.whatsapp.net', '@c.us'], '', $sender);

        try {
            // Process message and get response
            $response = $this->botService->handleIncomingMessage($phone, $message);

            return response()->json([
                'status' => 'success',
                'response_sent' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error', [
                'phone' => $phone,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify webhook (for testing)
     */
    public function verify(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'WhatsApp webhook is active',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
