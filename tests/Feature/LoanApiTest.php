<?php

use App\Models\Loan;
use App\Services\PaymentService;
use App\Services\UserAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const VALID_KEY   = '102022400337';
const INVALID_KEY = 'WRONG_KEY_999';

// ─────────────────────────────────────────────────────────────────────────────
// API Key Protection
// ─────────────────────────────────────────────────────────────────────────────

test('returns 401 when X-IAE-KEY header is missing', function () {
    $this->getJson('/api/v1/loans')
         ->assertStatus(401)
         ->assertJson(['status' => 'error']);
});

test('returns 401 when X-IAE-KEY header is wrong', function () {
    $this->getJson('/api/v1/loans', ['X-IAE-KEY' => INVALID_KEY])
         ->assertStatus(401)
         ->assertJson(['status' => 'error']);
});

test('returns 401 for POST with missing key', function () {
    $this->postJson('/api/v1/loans', [])
         ->assertStatus(401);
});

test('returns 401 for PATCH with missing key', function () {
    $loan = Loan::factory()->create();
    $this->patchJson("/api/v1/loans/{$loan->id}", ['status' => 'approved'])
         ->assertStatus(401);
});

test('returns 401 for DELETE with missing key', function () {
    $loan = Loan::factory()->create();
    $this->deleteJson("/api/v1/loans/{$loan->id}")
         ->assertStatus(401);
});

// ─────────────────────────────────────────────────────────────────────────────
// GET /api/v1/loans
// ─────────────────────────────────────────────────────────────────────────────

test('GET /api/v1/loans returns 200 with standard response format', function () {
    Loan::factory()->count(3)->create();

    $this->getJson('/api/v1/loans', ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(200)
         ->assertJsonStructure([
             'status', 'message',
             'data' => ['loans', 'pagination' => ['total', 'per_page', 'current_page', 'last_page']],
             'meta' => ['service_name', 'api_version'],
         ])
         ->assertJsonPath('status', 'success')
         ->assertJsonPath('meta.service_name', 'Loan-Service')
         ->assertJsonPath('meta.api_version', 'v1');
});

test('GET /api/v1/loans filters by status', function () {
    Loan::factory()->create(['status' => 'approved']);
    Loan::factory()->create(['status' => 'pending']);

    $response = $this->getJson('/api/v1/loans?status=approved', ['X-IAE-KEY' => VALID_KEY])
                     ->assertStatus(200);

    $loans = $response->json('data.loans');
    expect(collect($loans)->every(fn ($l) => $l['status'] === 'approved'))->toBeTrue();
});

test('GET /api/v1/loans returns empty list when no loans exist', function () {
    $response = $this->getJson('/api/v1/loans', ['X-IAE-KEY' => VALID_KEY])
                     ->assertStatus(200);

    expect($response->json('data.loans'))->toBeEmpty();
    expect($response->json('data.pagination.total'))->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// GET /api/v1/loans/{id}
// ─────────────────────────────────────────────────────────────────────────────

test('GET /api/v1/loans/{id} returns 200 for existing loan', function () {
    $loan = Loan::factory()->create();

    $this->getJson("/api/v1/loans/{$loan->id}", ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(200)
         ->assertJsonPath('data.loan.id', $loan->id)
         ->assertJsonPath('data.loan.applicant_nim', $loan->applicant_nim);
});

test('GET /api/v1/loans/{id} returns 404 for non-existent loan', function () {
    $this->getJson('/api/v1/loans/00000000-0000-0000-0000-000000000000', ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(404)
         ->assertJsonPath('status', 'error');
});

// ─────────────────────────────────────────────────────────────────────────────
// POST /api/v1/loans
// ─────────────────────────────────────────────────────────────────────────────

test('POST /api/v1/loans creates loan and returns 201', function () {
    $payload = [
        'applicant_name' => 'Budi Santoso',
        'applicant_nim'  => '102022400001',
        'amount'         => 5_000_000,
        'tenor_months'   => 12,
        'purpose'        => 'Modal usaha',
    ];

    $this->postJson('/api/v1/loans', $payload, ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(201)
         ->assertJsonPath('status', 'success')
         ->assertJsonPath('data.loan.applicant_name', 'Budi Santoso')
         ->assertJsonPath('data.loan.status', 'pending');

    $this->assertDatabaseHas('loans', ['applicant_nim' => '102022400001']);
});

test('POST /api/v1/loans returns 422 when required fields missing', function () {
    $this->postJson('/api/v1/loans', [], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(422)
         ->assertJsonPath('status', 'error');
});

test('POST /api/v1/loans returns 422 when amount is below minimum', function () {
    $payload = [
        'applicant_name' => 'Test User',
        'applicant_nim'  => '102022400099',
        'amount'         => 500,   // below 100000
        'tenor_months'   => 6,
        'purpose'        => 'Test purpose',
    ];

    $this->postJson('/api/v1/loans', $payload, ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(422);
});

test('POST /api/v1/loans returns 422 when tenor_months is 0', function () {
    $payload = [
        'applicant_name' => 'Test User',
        'applicant_nim'  => '102022400099',
        'amount'         => 5_000_000,
        'tenor_months'   => 0,
        'purpose'        => 'Test purpose',
    ];

    $this->postJson('/api/v1/loans', $payload, ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(422);
});

// ─── User/Auth Service integration on POST ───────────────────────────────────

test('POST /api/v1/loans returns 404 when applicant_user_id not found in User/Auth Service', function () {
    // Mock UserAuthService to return false (user not found)
    $this->instance(UserAuthService::class, new class extends UserAuthService {
        public function userExists(string $userId): bool { return false; }
    });

    $payload = [
        'applicant_name'    => 'Ghost User',
        'applicant_nim'     => '102022400099',
        'applicant_user_id' => 'non-existent-user-id',
        'amount'            => 5_000_000,
        'tenor_months'      => 12,
        'purpose'           => 'Test',
    ];

    $this->postJson('/api/v1/loans', $payload, ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(404)
         ->assertJsonPath('status', 'error');
});

test('POST /api/v1/loans succeeds when User/Auth Service confirms user exists', function () {
    $this->instance(UserAuthService::class, new class extends UserAuthService {
        public function userExists(string $userId): bool { return true; }
    });

    $payload = [
        'applicant_name'    => 'Valid User',
        'applicant_nim'     => '102022400010',
        'applicant_user_id' => 'valid-user-uuid',
        'amount'            => 5_000_000,
        'tenor_months'      => 12,
        'purpose'           => 'Pendidikan',
    ];

    $this->postJson('/api/v1/loans', $payload, ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(201);
});

// ─────────────────────────────────────────────────────────────────────────────
// PATCH /api/v1/loans/{id}
// ─────────────────────────────────────────────────────────────────────────────

test('PATCH /api/v1/loans/{id} updates status and returns 200', function () {
    $loan = Loan::factory()->pending()->create();

    $this->patchJson("/api/v1/loans/{$loan->id}", ['status' => 'approved'], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(200)
         ->assertJsonPath('data.loan.status', 'approved');
});

test('PATCH /api/v1/loans/{id} returns 404 for non-existent loan', function () {
    $this->patchJson('/api/v1/loans/no-such-id', ['status' => 'approved'], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(404);
});

test('PATCH /api/v1/loans/{id} returns 422 for invalid status', function () {
    $loan = Loan::factory()->create();

    $this->patchJson("/api/v1/loans/{$loan->id}", ['status' => 'flying'], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(422);
});

// ─── Payment Service integration on PATCH ────────────────────────────────────

test('PATCH to disbursed calls PaymentService::createSchedule', function () {
    $called  = false;
    $capturedPayload = [];

    $this->instance(PaymentService::class, new class($called, $capturedPayload) extends PaymentService {
        public function __construct(private bool &$called, private array &$payload) {}
        public function createSchedule(array $payload): bool {
            $this->called  = true;
            $this->payload = $payload;
            return true;
        }
    });

    $loan = Loan::factory()->approved()->create();

    $this->patchJson("/api/v1/loans/{$loan->id}", ['status' => 'disbursed'], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(200)
         ->assertJsonPath('data.loan.status', 'disbursed');

    expect($called)->toBeTrue();
    expect($capturedPayload['loan_id'])->toBe($loan->id);
});

test('PATCH to approved does NOT call PaymentService', function () {
    $called = false;

    $this->instance(PaymentService::class, new class($called) extends PaymentService {
        public function __construct(private bool &$called) {}
        public function createSchedule(array $payload): bool { $this->called = true; return true; }
    });

    $loan = Loan::factory()->pending()->create();

    $this->patchJson("/api/v1/loans/{$loan->id}", ['status' => 'approved'], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(200);

    expect($called)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// DELETE /api/v1/loans/{id}
// ─────────────────────────────────────────────────────────────────────────────

test('DELETE /api/v1/loans/{id} soft-deletes the loan', function () {
    $loan = Loan::factory()->create();

    $this->deleteJson("/api/v1/loans/{$loan->id}", [], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(200)
         ->assertJsonPath('status', 'success');

    $this->assertSoftDeleted('loans', ['id' => $loan->id]);
});

test('DELETE /api/v1/loans/{id} returns 404 for non-existent loan', function () {
    $this->deleteJson('/api/v1/loans/no-such-id', [], ['X-IAE-KEY' => VALID_KEY])
         ->assertStatus(404);
});

// ─────────────────────────────────────────────────────────────────────────────
// Health
// ─────────────────────────────────────────────────────────────────────────────

test('GET /api/v1/loans/health returns 200 with service info', function () {
    $response = $this->getJson('/api/v1/loans/health', ['X-IAE-KEY' => VALID_KEY])
                     ->assertStatus(200)
                     ->assertJsonPath('data.service', 'Loan-Service')
                     ->assertJsonPath('data.nim', '102022400337');
});
