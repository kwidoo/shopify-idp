<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Symfony\Component\HttpFoundation\Response;

class AuthRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        $maxAttempts = config('auth.rate_limits.max_attempts', 5);
        $decayMinutes = config('auth.rate_limits.decay_minutes', 1);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($key);

            // Log rate limit exceeded
            Log::channel('auth')->warning('Rate limit exceeded for authentication', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'available_in_seconds' => $seconds
            ]);

            return $this->buildResponse($request, $key, $maxAttempts, $seconds);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            RateLimiter::remaining($key, $maxAttempts),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve request signature for rate limiting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature($request): string
    {
        $signature = $request->ip();

        if ($user = $request->user()) {
            $signature .= '|' . $user->getAuthIdentifier();
        } else if ($email = $request->input('email')) {
            $signature .= '|' . Str::lower($email);
        } else if ($shop = $request->input('shop')) {
            $signature .= '|' . $shop;
        }

        return sha1('auth_rate_limit:' . $signature);
    }

    /**
     * Create a response for when the rate limit is exceeded.
     */
    protected function buildResponse(Request $request, string $key, int $maxAttempts, int $seconds): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
                'seconds_remaining' => $seconds
            ], 429);
        }

        return redirect()
            ->back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
            ]);
    }

    /**
     * Add rate limit headers to the response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts, int $retryAfter = null): Response
    {
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
            $retryAfter ? 'Retry-After' : '' => $retryAfter,
        ]);
    }
}
