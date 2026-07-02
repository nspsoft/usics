<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PurchasingWhatsappBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchasingWhatsappWebhookController extends Controller
{
    protected PurchasingWhatsappBotService $botService;

    public function __construct(PurchasingWhatsappBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Handle incoming webhook from Fonnte or Wablas for Purchasing
     */
    public function handle(Request $request)
    {
        Log::info('Purchasing Webhook Payload:', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'json' => $request->json()->all(),
        ]);

        // Wablas uses 'phone' for sender, Fonnte uses 'sender'
        $phone = $request->input('phone') ?: $request->input('sender');
        $message = $request->input('message');
        $pushName = $request->input('pushName'); // Wablas specific
        
        // Audio notes detection & transcription
        $mediaUrl = $request->input('url') ?: $request->input('media');
        $extension = strtolower((string)$request->input('extension'));
        $type = strtolower((string)$request->input('type'));

        $isAudio = in_array($extension, ['ogg', 'mp3', 'wav', 'm4a', 'aac', 'amr']) || 
                   in_array($type, ['audio', 'voice', 'ptt']) ||
                   ($mediaUrl && (str_contains($mediaUrl, '.ogg') || str_contains($mediaUrl, '.mp3') || str_contains($mediaUrl, '.wav') || str_contains($mediaUrl, '.m4a') || str_contains($mediaUrl, '.amr') || str_contains($mediaUrl, 'voice')));

        if ($isAudio && $mediaUrl) {
            /** @var \App\Services\GeminiService $gemini */
            $gemini = app(\App\Services\GeminiService::class);
            $transcription = $gemini->transcribeAudioFromUrl($mediaUrl);
            if ($transcription) {
                $message = $transcription;
            } else {
                Log::warning('Audio transcription failed or returned empty. Falling back to original message.');
            }
        }

        if (!$phone || !$message) {
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $isGroup = str_contains($phone, '@g.us') || $request->input('isGroup');

        // Ignore group messages
        if ($isGroup) {
            return response()->json(['status' => 'ignored', 'reason' => 'group_message']);
        }

        // Clean phone number
        $phone = str_replace(['@s.whatsapp.net', '@c.us'], '', $phone);

        try {
            // Process message and get response
            $response = $this->botService->handleIncomingMessage($phone, $message, $pushName);

            return response()->json([
                'status' => 'success',
                'response_sent' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Purchasing WhatsApp Webhook Error', [
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
     * Verify webhook
     */
    public function verify(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Purchasing WhatsApp webhook is active',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
