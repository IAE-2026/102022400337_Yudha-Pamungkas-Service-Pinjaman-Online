<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memastikan setiap response dari API (/api/v1/*) secara eksplisit
 * memiliki header Content-Type: application/json.
 *
 * Laravel's response()->json() sudah mengatur header ini secara default,
 * namun middleware ini menjadikannya eksplisit & terdokumentasi sebagai
 * bagian dari standar service (sesuai checklist Security & Standard).
 */
class ForceJsonContentType
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
