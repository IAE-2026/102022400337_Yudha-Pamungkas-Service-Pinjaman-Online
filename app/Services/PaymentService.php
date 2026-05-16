<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for the Payment Service.
 *
 * Responsibility: when a loan status changes to "disbursed", notify the
 * Payment Service so it can create the repayment schedule.
 *
 * Inter-service contract (assumed):
 *   POST {PAYMENT_SERVICE_URL}/api/v1/schedules
 *   Header: X-IAE-KEY: <their NIM>
 *   Body: { loan_id, applicant_nim, amount, tenor_months, disbursed_at }
 *
 * Fails gracefully: loan disbursement is NOT rolled back if this call fails.
 * The operations team must reconcile manually. Log every failure.
 */
class PaymentService
{
    private string $baseUrl;
    private string $iaeKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.payment.url', ''), '/');
        $this->iaeKey  = config('app.iae_key', '102022400337');
    }

    /**
     * Create a payment schedule for a disbursed loan.
     *
     * @param  array{
     *     loan_id: string,
     *     applicant_nim: string,
     *     amount: string|float,
     *     tenor_months: int,
     *     disbursed_at: string,
     * } $payload
     */
    public function createSchedule(array $payload): bool
    {
        if (empty($this->baseUrl)) {
            Log::warning('PaymentService: PAYMENT_SERVICE_URL not configured. Schedule not created.', $payload);
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-IAE-KEY' => $this->iaeKey])
                ->post("{$this->baseUrl}/api/v1/schedules", $payload);

            if ($response->successful()) {
                Log::info('PaymentService: Schedule created.', ['loan_id' => $payload['loan_id']]);
                return true;
            }

            Log::error('PaymentService: Failed to create schedule.', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'payload' => $payload,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('PaymentService: Request exception.', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            return false;
        }
    }
}
