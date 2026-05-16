<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the User/Auth Service.
 *
 * Responsibility: verify that an applicant_user_id actually exists in the
 * User/Auth Service before accepting a loan application.
 *
 * Inter-service contract (assumed):
 *   GET {USER_AUTH_SERVICE_URL}/api/v1/users/{userId}
 *   Header: X-IAE-KEY: <their NIM>
 *   Response 200 → user exists
 *   Response 404 → user not found
 *
 * Fails gracefully: if the service is unreachable, we allow the loan to be
 * created and log a warning. Adjust this policy if the team decides otherwise.
 */
class UserAuthService
{
    private string $baseUrl;
    private string $iaeKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.user_auth.url', ''), '/');
        $this->iaeKey  = config('app.iae_key', '102022400337');
    }

    /**
     * Returns true if the user ID is valid (or if the service is unavailable).
     * Returns false only when the service explicitly says the user does not exist.
     */
    public function userExists(string $userId): bool
    {
        if (empty($this->baseUrl)) {
            Log::warning('UserAuthService: USER_AUTH_SERVICE_URL not configured. Skipping validation.');
            return true;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders(['X-IAE-KEY' => $this->iaeKey])
                ->get("{$this->baseUrl}/api/v1/users/{$userId}");

            if ($response->status() === 404) {
                return false;
            }

            if ($response->successful()) {
                return true;
            }

            Log::warning('UserAuthService: Unexpected response.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return true; // allow on unexpected errors
        } catch (\Exception $e) {
            Log::error('UserAuthService: Request failed.', ['error' => $e->getMessage()]);
            return true; // allow on network errors
        }
    }
}
