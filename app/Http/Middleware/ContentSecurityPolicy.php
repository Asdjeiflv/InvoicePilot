<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Development environment CSP - allows Vite HMR
        if (app()->environment('local')) {
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-eval' 'unsafe-inline' https://fonts.bunny.net http://localhost:* http://127.0.0.1:*",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data: https:",
                "connect-src 'self' ws://localhost:* ws://127.0.0.1:* http://localhost:* http://127.0.0.1:*",
            ]);
        } else {
            // Production environment CSP - more restrictive
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' https://fonts.bunny.net",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data: https:",
                "connect-src 'self'",
            ]);
        }

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
