<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class IaeCloudSsoService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            env('IAE_CLOUD_URL', 'https://iae-sso.virtualfri.id'),
            '/'
        );
    }

    public function getMachineToken(): ?string
    {
        $response = Http::acceptJson()
            ->post($this->baseUrl . '/api/v1/auth/token', [
                'api_key' => env('IAE_CLOUD_API_KEY')
            ]);

        if (! $response->successful()) {
            return null;
        }

        $body = $response->json();

        return $body['token']
            ?? $body['access_token']
            ?? null;
    }
}