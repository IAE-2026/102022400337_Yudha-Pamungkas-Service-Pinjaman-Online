<?php

use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Middleware\VerifyIaeKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Loan Service — API Routes
| NIM: 102022400337
|--------------------------------------------------------------------------
|
| All routes under /api/v1/ are protected by X-IAE-KEY middleware.
| The /health endpoint is listed inside the protected group so it is also
| documented in Swagger under the same security scheme, but the controller
| method itself can be called without a key if needed for Docker healthchecks
| — adjust the middleware order below if your team decides otherwise.
|
*/

Route::prefix('v1')->group(function () {

    // Health (protected — demonstrates auth on all endpoints)
    Route::middleware([VerifyIaeKey::class])->group(function () {
        Route::get('/loans/health', [LoanController::class, 'health']);

        // Loan CRUD
        Route::get('/loans',         [LoanController::class, 'index']);
        Route::get('/loans/{id}',    [LoanController::class, 'show']);
        Route::post('/loans',        [LoanController::class, 'store']);
        Route::patch('/loans/{id}',  [LoanController::class, 'update']);
        Route::delete('/loans/{id}', [LoanController::class, 'destroy']);
    });
});

// 404 fallback for unmatched /api/* routes
Route::fallback(function () {
    return response()->json([
        'status'  => 'error',
        'message' => 'Endpoint not found.',
        'errors'  => null,
    ], 404);
});
