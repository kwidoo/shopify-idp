<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopifyRateLimiter
{
    private int $maxCalls;
    private int $perSeconds;
    private string $cachePrefix = 'shopify_api_rate_limit:';

    public function __construct(int $maxCalls = null, int $perSeconds = null)
    {
        $this->maxCalls = $maxCalls ?? config('services.shopify.api_rate_limit_calls', 2);
        $this->perSeconds = $perSeconds ?? config('services.shopify.api_rate_limit_seconds', 1);
    }

    /**
     * Handle rate limiting for a specific endpoint and shop combination
     *
     * @param string $shop The shop domain
     * @param string $endpoint The API endpoint being called
     * @return bool Whether the request can proceed or should be throttled
     */
    public function throttle(string $shop, string $endpoint): bool
    {
        $key = $this->getKey($shop, $endpoint);
        $callCount = Cache::get($key, 0);

        if ($callCount >= $this->maxCalls) {
            Log::warning('Shopify API rate limit reached', [
                'shop' => $shop,
                'endpoint' => $endpoint,
                'max_calls' => $this->maxCalls,
                'per_seconds' => $this->perSeconds
            ]);

            return true; // Should be throttled
        }

        // Increment the counter
        if ($callCount === 0) {
            Cache::put($key, 1, now()->addSeconds($this->perSeconds));
        } else {
            Cache::increment($key);
        }

        return false; // Can proceed
    }

    /**
     * Wait until the rate limit window resets
     *
     * @param string $shop The shop domain
     * @param string $endpoint The API endpoint being called
     * @return void
     */
    public function waitForReset(string $shop, string $endpoint): void
    {
        $key = $this->getKey($shop, $endpoint);
        $ttl = Cache::getTtl($key);

        if ($ttl > 0) {
            // Add a small buffer to ensure the rate limit has fully reset
            usleep(($ttl + 0.1) * 1000000);
        }
    }

    /**
     * Get the cache key for a specific shop and endpoint
     *
     * @param string $shop
     * @param string $endpoint
     * @return string
     */
    private function getKey(string $shop, string $endpoint): string
    {
        // Extract the base endpoint without query params and use last path segment
        $path = parse_url($endpoint, PHP_URL_PATH) ?? $endpoint;
        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);

        return $this->cachePrefix . md5($shop . ':' . $lastSegment);
    }
}
