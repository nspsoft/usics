<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected string $apiToken;
    protected string $baseUrl = 'https://api.fonnte.com';

    public function __construct()
    {
        // Try to get from database first, fallback to .env config
        $this->apiToken = (string) (\App\Models\AppSetting::get('fonnte_api_token') ?: config('services.fonnte.token', ''));
    }

    /**
     * Send a text message via Fonnte
     */
    public function sendMessage(string $phone, string $message): array
    {
        if (empty($this->apiToken)) {
            Log::error('Fonnte API Token is not configured');
            return ['success' => false, 'error' => 'API Token not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post("{$this->baseUrl}/send", [
                'target' => $this->formatPhone($phone),
                'message' => $message,
                'countryCode' => '62',
            ]);

            $result = $response->json();
            
            Log::info('Fonnte Send Response', [
                'phone' => $phone,
                'status' => $result['status'] ?? 'unknown',
            ]);

            return [
                'success' => ($result['status'] ?? false) === true,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Fonnte Send Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a message with image
     */
    public function sendImage(string $phone, string $imageUrl, string $caption = ''): array
    {
        if (empty($this->apiToken)) {
            return ['success' => false, 'error' => 'API Token not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post("{$this->baseUrl}/send", [
                'target' => $this->formatPhone($phone),
                'message' => $caption,
                'url' => $imageUrl,
                'countryCode' => '62',
            ]);

            return [
                'success' => ($response->json()['status'] ?? false) === true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Fonnte Send Image Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        
        // If doesn't start with 62, add it
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Validate webhook signature (if needed)
     */
    public function validateWebhook(array $payload): bool
    {
        // Fonnte doesn't require signature validation by default
        // Add custom validation logic if needed
        return true;
    }
}
