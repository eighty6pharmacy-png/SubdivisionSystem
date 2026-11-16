<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (method_exists($response, 'headers')) {
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net https://cdn.onesignal.com; " .
                "style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com; " .
                "font-src 'self' data: https://fonts.gstatic.com; " .
                "img-src 'self' data: blob: https:; " .
                "connect-src 'self' https: wss:;"
            );
        }

        return $response;
    }
}
