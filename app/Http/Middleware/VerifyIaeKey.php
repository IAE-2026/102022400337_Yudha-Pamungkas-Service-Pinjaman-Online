<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIaeKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-IAE-KEY');
        $expectedKey = config('app.iae_key');

        if (empty($providedKey)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'API key missing. Include the X-IAE-KEY header.',
                'errors'  => null,
            ], 401);
        }

        if ($providedKey !== $expectedKey) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid API key.',
                'errors'  => null,
            ], 401);
        }

        return $next($request);
    }
}
