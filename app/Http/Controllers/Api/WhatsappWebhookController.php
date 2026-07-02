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
        Log::info('Webhook Payload:', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'json' => $request->json()->all(),
        ]);
        // Log::info('Webhook Payload', $request->all());

        // Wablas uses 'phone' for sender, Fonnte uses 'sender'
        // Prioritize 'phone' as it's more likely to be the user in hybrid scenarios
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
            $response = $this->botService->handleIncomingMessage($phone, $message, $pushName, $mediaUrl);

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
