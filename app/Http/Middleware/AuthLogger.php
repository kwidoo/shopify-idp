<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthLogger
{
    /**
     * Handle an incoming request and log authentication attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log authentication attempt with relevant information
        Log::channel('auth')->info('Authentication attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'email' => $request->input('email') ? substr($request->input('email'), 0, 3) . '***' : 'none', // Partial email for privacy
            'shopify_url' => $request->input('shop'),
            'method' => $request->method(),
        ]);

        // Process the request
        $response = $next($request);

        // Log authentication result
        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300 && $request->session()->has('auth.success')) {
            Log::channel('auth')->info('Authentication successful', [
                'ip' => $request->ip(),
                'user' => $request->session()->get('auth.user', 'unknown'),
                'path' => $request->path(),
            ]);
            $request->session()->forget('auth.success');
        } elseif (($statusCode >= 400 || $request->session()->has('auth.failed')) &&
            (str_contains($request->path(), 'login') || str_contains($request->path(), 'auth/shopify'))
        ) {
            Log::channel('auth')->warning('Authentication failed', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'error' => $request->session()->get('auth.error', 'Unknown error'),
            ]);
            $request->session()->forget(['auth.failed', 'auth.error']);
        }

        return $response;
    }
}
