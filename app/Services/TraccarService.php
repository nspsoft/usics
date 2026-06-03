<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class TraccarService
{
    public function baseUrl(): string
    {
        return rtrim((string) AppSetting::get('traccar_base_url', ''), '/');
    }

    public function username(): string
    {
        return (string) AppSetting::get('traccar_username', '');
    }

    public function password(): string
    {
        $raw = (string) AppSetting::get('traccar_password', '');
        if ($raw === '') {
            return '';
        }

        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            return $raw;
        }
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl() !== '' && $this->username() !== '' && $this->password() !== '';
    }

    protected function client()
    {
        return Http::acceptJson()
            ->timeout(15)
            ->withBasicAuth($this->username(), $this->password());
    }

    protected function url(string $path): string
    {
        return $this->baseUrl() . '/api/' . ltrim($path, '/');
    }

    public function getDevices(): array
    {
        $response = $this->client()->get($this->url('devices'));
        $response->throw();
        return $response->json() ?: [];
    }

    public function getPositions(): array
    {
        $response = $this->client()->get($this->url('positions'));
        $response->throw();
        return $response->json() ?: [];
    }

    public function getRoute(int $deviceId, string $fromIso, string $toIso): array
    {
        $response = $this->client()->get($this->url('reports/route'), [
            'deviceId' => $deviceId,
            'from' => $fromIso,
            'to' => $toIso,
        ]);
        $response->throw();
        return $response->json() ?: [];
    }
}

