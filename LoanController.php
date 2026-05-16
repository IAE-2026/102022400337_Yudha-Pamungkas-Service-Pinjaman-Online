<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\PaymentService;
use App\Services\UserAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Loan Service API",
 *     description="Service Pinjaman — PENGAJUAN PINJAMAN DIGITAL.\nNIM: 102022400337\n\nAuthenticate every request with header: **X-IAE-KEY: 102022400337**",
 *     @OA\Contact(email="102022400337@student.telkomuniversity.ac.id"),
 * )
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="Loan Service")
 *
 * @OA\SecurityScheme(
 *     securityScheme="IaeKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-IAE-KEY",
 *     description="Your NIM: 102022400337"
 * )
 *
 * @OA\Schema(
 *     schema="Loan",
 *     type="object",
 *     @OA\Property(property="id",                type="string", format="uuid", example="018e5b2a-1234-7000-abcd-000000000001"),
 *     @OA\Property(property="applicant_name",    type="string", example="Budi Santoso"),
 *     @OA\Property(property="applicant_nim",     type="string", example="102022400001"),
 *     @OA\Property(property="applicant_user_id", type="string", nullable=true, example="user-uuid-from-auth-service"),
 *     @OA\Property(property="amount",            type="number", format="float", example=5000000),
 *     @OA\Property(property="tenor_months",      type="integer", example=12),
 *     @OA\Property(property="purpose",           type="string", example="Modal usaha"),
 *     @OA\Property(property="status",            type="string", enum={"pending","approved","rejected","disbursed"}),
 *     @OA\Property(property="notes",             type="string", nullable=true),
 *     @OA\Property(property="created_at",        type="string", format="date-time"),
 *     @OA\Property(property="updated_at",        type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="status",  type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Operation successful"),
 *     @OA\Property(property="data",    type="object"),
 *     @OA\Property(property="meta",    type="object",
 *         @OA\Property(property="service_name", type="string", example="Loan-Service"),
 *         @OA\Property(property="api_version",  type="string", example="v1"),
 *     ),
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="status",  type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="Detail pesan kesalahan."),
 *     @OA\Property(property="errors",  nullable=true),
 * )
 */
class LoanController extends Controller
{
    public function __construct(
        private readonly UserAuthService $userAuthService,
        private readonly PaymentService  $paymentService,
    ) {}

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function success(mixed $data, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => [
                'service_name' => 'Loan-Service',
                'api_version'  => 'v1',
            ],
        ], $status);
    }

    private function error(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    // ─── Endpoints ───────────────────────────────────────────────────────────

    /**
     * @OA\Get(
     *     path="/api/v1/loans",
     *     summary="List all loan applications",
     *     description="Returns a paginated list of loan applications. Optionally filter by status.",
     *     tags={"Loans"},
     *     security={{"IaeKey": {}}},
     *     @OA\Parameter(name="status",   in="query", required=false, description="Filter by status", @OA\Schema(type="string", enum={"pending","approved","rejected","disbursed"})),
     *     @OA\Parameter(name="page",     in="query", required=false, @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=15)),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Loan::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $loans   = $query->latest()->paginate($perPage);

        return $this->success([
            'loans'      => $loans->items(),
            'pagination' => [
                'total'        => $loans->total(),
                'per_page'     => $loans->perPage(),
                'current_page' => $loans->currentPage(),
                'last_page'    => $loans->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/loans/{id}",
     *     summary="Get a single loan by ID",
     *     tags={"Loans"},
     *     security={{"IaeKey": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Loan UUID", @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Success",      @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Not Found",    @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * )
     */
    public function show(string $id): JsonResponse
    {
        $loan = Loan::find($id);

        if (! $loan) {
            return $this->error('Loan not found.', null, 404);
        }

        return $this->success(['loan' => $loan]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/loans",
     *     summary="Submit a new loan application",
     *     description="Creates a loan. Optionally validates applicant_user_id against the User/Auth Service.",
     *     tags={"Loans"},
     *     security={{"IaeKey": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"applicant_name","applicant_nim","amount","tenor_months","purpose"},
     *             @OA\Property(property="applicant_name",    type="string",  example="Budi Santoso"),
     *             @OA\Property(property="applicant_nim",     type="string",  example="102022400001"),
     *             @OA\Property(property="applicant_user_id", type="string",  example="user-uuid", description="Optional: user ID from User/Auth Service"),
     *             @OA\Property(property="amount",            type="number",  example=5000000),
     *             @OA\Property(property="tenor_months",      type="integer", example=12),
     *             @OA\Property(property="purpose",           type="string",  example="Modal usaha"),
     *             @OA\Property(property="notes",             type="string",  nullable=true),
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created",           @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=401, description="Unauthorized",      @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="User not found",    @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error",  @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_name'    => 'required|string|max:255',
            'applicant_nim'     => 'required|string|max:50',
            'applicant_user_id' => 'nullable|string|max:100',
            'amount'            => 'required|numeric|min:100000|max:500000000',
            'tenor_months'      => 'required|integer|min:1|max:360',
            'purpose'           => 'required|string|max:255',
            'notes'             => 'nullable|string',
        ]);

        // Validate applicant against User/Auth Service (if ID supplied)
        if (! empty($validated['applicant_user_id'])) {
            if (! $this->userAuthService->userExists($validated['applicant_user_id'])) {
                return $this->error(
                    'Applicant user ID not found in User/Auth Service.',
                    null,
                    404
                );
            }
        }

        $loan = Loan::create([
            ...$validated,
            'status' => Loan::STATUS_PENDING,
        ]);

        return $this->success(['loan' => $loan], 'Loan application submitted successfully.', 201);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/loans/{id}",
     *     summary="Update loan status or notes",
     *     description="When status changes to 'disbursed', the Payment Service is notified to create a repayment schedule.",
     *     tags={"Loans"},
     *     security={{"IaeKey": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending","approved","rejected","disbursed"}),
     *             @OA\Property(property="notes",  type="string", nullable=true),
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated",           @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=401, description="Unauthorized",      @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Not Found",         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error",  @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $loan = Loan::find($id);

        if (! $loan) {
            return $this->error('Loan not found.', null, 404);
        }

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(Loan::STATUSES)],
            'notes'  => 'sometimes|nullable|string',
        ]);

        $oldStatus = $loan->status;
        $loan->update($validated);
        $loan->refresh();

        // If status just changed to "disbursed" → notify Payment Service
        if (
            isset($validated['status'])
            && $validated['status'] === Loan::STATUS_DISBURSED
            && $oldStatus !== Loan::STATUS_DISBURSED
        ) {
            $this->paymentService->createSchedule([
                'loan_id'       => $loan->id,
                'applicant_nim' => $loan->applicant_nim,
                'amount'        => (float) $loan->amount,
                'tenor_months'  => $loan->tenor_months,
                'disbursed_at'  => now()->toIso8601String(),
            ]);
        }

        return $this->success(['loan' => $loan], 'Loan updated successfully.');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/loans/{id}",
     *     summary="Delete a loan application (soft delete)",
     *     tags={"Loans"},
     *     security={{"IaeKey": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Deleted",      @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=404, description="Not Found",    @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        $loan = Loan::find($id);

        if (! $loan) {
            return $this->error('Loan not found.', null, 404);
        }

        $loan->delete();

        return $this->success(null, 'Loan deleted successfully.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/loans/health",
     *     summary="Service health check",
     *     description="Returns service metadata and current timestamp. No API key required.",
     *     tags={"Health"},
     *     @OA\Response(response=200, description="Healthy"),
     * )
     */
    public function health(): JsonResponse
    {
        return $this->success([
            'service'           => 'Loan-Service',
            'nim'               => '102022400337',
            'status'            => 'healthy',
            'connected_services'=> [
                'user_auth' => config('services.user_auth.url') ?: 'not configured',
                'payment'   => config('services.payment.url')   ?: 'not configured',
            ],
            'timestamp' => now()->toIso8601String(),
        ], 'Service is healthy.');
    }
}
