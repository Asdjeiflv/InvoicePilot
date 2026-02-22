<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to POST/PUT/PATCH requests
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        // Check if Idempotency-Key header exists
        $key = $request->header('Idempotency-Key');
        
        if (!$key) {
            // No idempotency key provided, proceed normally
            return $next($request);
        }

        // Check if this key was already processed
        $cached = IdempotencyKey::where('key', $key)
            ->where('user_id', auth()->id())
            ->where('created_at', '>', now()->subHours(24))
            ->first();

        if ($cached) {
            // Return cached response
            $response = response($cached->response_json, $cached->response_status);

            // Only set JSON content type for non-redirect responses
            if ($cached->response_status < 300 || $cached->response_status >= 400) {
                $response->header('Content-Type', 'application/json');
            }

            return $response->header('X-Idempotency-Replay', 'true');
        }

        // Process the request
        $response = $next($request);

        // Cache successful and redirect responses (2xx and 3xx)
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 400) {
            try {
                IdempotencyKey::create([
                    'key' => $key,
                    'user_id' => auth()->id(),
                    'response_json' => $response->getContent(),
                    'response_status' => $response->getStatusCode(),
                ]);
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::error('Failed to store idempotency key', [
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $response;
    }
}
