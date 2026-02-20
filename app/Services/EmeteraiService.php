<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmeteraiService
{
    protected ?string $clientId;
    protected ?string $secretKey;
    protected bool $enabled;
    protected string $baseUrl = 'https://api.e-meterai.co.id/api/v1';

    public function __construct()
    {
        $company = \App\Models\Company::first();
        $emeteraiSettings = $company->settings['emeterai'] ?? [];
        $this->enabled = (bool) ($emeteraiSettings['enabled'] ?? false);
        $this->clientId = $emeteraiSettings['client_id'] ?? null;
        $this->secretKey = $emeteraiSettings['secret_key'] ?? null;
    }

    /**
     * Check if e-Meterai API credentials are configured.
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->clientId) && !empty($this->secretKey);
    }

    /**
     * Check if e-Meterai feature is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get authentication token from Peruri API.
     */
    protected function getToken(): ?string
    {
        try {
            $response = Http::post("{$this->baseUrl}/auth/login", [
                'client_id' => $this->clientId,
                'client_secret' => $this->secretKey,
            ]);

            if ($response->successful()) {
                return $response->json('token');
            }

            Log::error('e-Meterai auth failed', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('e-Meterai auth error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Stamp a PDF document with e-Meterai.
     *
     * @param string $pdfPath Absolute path to the PDF file
     * @return array ['success' => bool, 'serial' => string|null, 'stamped_pdf_path' => string|null, 'error' => string|null]
     */
    public function stampDocument(string $pdfPath): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'serial' => null,
                'stamped_pdf_path' => null,
                'error' => 'e-Meterai API belum dikonfigurasi. Silakan isi Client ID dan Secret Key di Settings.',
            ];
        }

        $token = $this->getToken();
        if (!$token) {
            return [
                'success' => false,
                'serial' => null,
                'stamped_pdf_path' => null,
                'error' => 'Gagal autentikasi ke Peruri API. Periksa Client ID dan Secret Key.',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->attach('file', file_get_contents($pdfPath), basename($pdfPath))
                ->post("{$this->baseUrl}/stamp", [
                    'position' => 'bottom-right',
                    'page' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $serial = $data['serial_number'] ?? $data['sn'] ?? null;

                // Save the stamped PDF
                $stampedFilename = 'emeterai/' . now()->format('Ymd') . '_' . basename($pdfPath);
                
                if (isset($data['stamped_file'])) {
                    Storage::disk('public')->put($stampedFilename, base64_decode($data['stamped_file']));
                }

                Log::info('e-Meterai stamped successfully', ['serial' => $serial, 'file' => $pdfPath]);

                return [
                    'success' => true,
                    'serial' => $serial,
                    'stamped_pdf_path' => $stampedFilename,
                    'error' => null,
                ];
            }

            Log::error('e-Meterai stamp failed', ['response' => $response->body()]);
            return [
                'success' => false,
                'serial' => null,
                'stamped_pdf_path' => null,
                'error' => 'Gagal membubuhkan e-Meterai: ' . ($response->json('message') ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('e-Meterai stamp error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'serial' => null,
                'stamped_pdf_path' => null,
                'error' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check remaining e-Meterai balance/quota.
     */
    public function checkBalance(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'balance' => 0, 'error' => 'API belum dikonfigurasi.'];
        }

        $token = $this->getToken();
        if (!$token) {
            return ['success' => false, 'balance' => 0, 'error' => 'Gagal autentikasi.'];
        }

        try {
            $response = Http::withToken($token)->get("{$this->baseUrl}/balance");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json('balance') ?? $response->json('quota') ?? 0,
                    'error' => null,
                ];
            }

            return ['success' => false, 'balance' => 0, 'error' => $response->json('message') ?? 'Unknown error'];
        } catch (\Exception $e) {
            return ['success' => false, 'balance' => 0, 'error' => $e->getMessage()];
        }
    }
}
