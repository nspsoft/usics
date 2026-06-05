<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WablasService
{
    protected string $apiToken;
    protected string $baseUrl = 'https://pati.wablas.com';

    public function __construct()
    {
        // Try to get from database first, fallback to .env config
        $this->apiToken = (string) (\App\Models\AppSetting::get('wablas_api_token') ?: config('services.wablas.token', ''));
        $this->baseUrl = \App\Models\AppSetting::get('wablas_server_url') ?: config('services.wablas.url', 'https://pati.wablas.com');
    }

    /**
     * Set credentials dynamically (e.g. for purchasing module)
     */
    public function setCredentials(string $apiToken, ?string $baseUrl = null): self
    {
        $this->apiToken = $apiToken;
        if ($baseUrl) {
            $this->baseUrl = $baseUrl;
        }
        return $this;
    }

    /**
     * Send a text message via Wablas
     */
    public function sendMessage(string $phone, string $message): array
    {
        if (empty($this->apiToken)) {
            Log::error('Wablas API Token is not configured');
            return ['success' => false, 'error' => 'API Token not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post("{$this->baseUrl}/api/send-message", [
                'phone' => $this->formatPhone($phone),
                'message' => $message,
            ]);

            $result = $response->json();
            
            Log::info('Wablas Send Response', [
                'phone' => $phone,
                'status' => is_array($result) ? ($result['status'] ?? 'unknown') : 'non-json-response',
            ]);

            return [
                'success' => is_array($result) && ($result['status'] ?? false) === true,
                'data' => $result,
                'error' => (is_array($result) && ($result['status'] ?? false) === true) ? null : (is_array($result) ? ($result['message'] ?? 'Wablas API Error') : 'Invalid Response Format'),
            ];
        } catch (\Throwable $e) {
            Log::error('Wablas Send Error: ' . $e->getMessage());
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
            ])->post("{$this->baseUrl}/api/send-image", [
                'phone' => $this->formatPhone($phone),
                'image' => $imageUrl,
                'caption' => $caption,
            ]);

            return [
                'success' => ($response->json()['status'] ?? false) === true,
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Wablas Send Image Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a file/document
     */
    public function sendFile(string $phone, string $fileUrl, string $caption = ''): array
    {
        if (empty($this->apiToken)) {
            return ['success' => false, 'error' => 'API Token not configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
            ])->post("{$this->baseUrl}/api/send-document", [
                'phone' => $this->formatPhone($phone),
                'document' => $fileUrl,
                'caption' => $caption,
            ]);

            $result = $response->json();

            return [
                'success' => is_array($result) && ($result['status'] ?? false) === true,
                'data' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error('Wablas Send File Error: ' . $e->getMessage());
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
        return true;
    }
}
