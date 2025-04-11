<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyShopifyWebhook
{
    /**
     * Verify that the request is from Shopify using HMAC validation
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-SHA256');

        if (!$hmacHeader) {
            Log::warning('Webhook request missing HMAC header');
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->getContent();
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, config('services.shopify.webhook_secret'), true));

        if (!hash_equals($calculatedHmac, $hmacHeader)) {
            Log::warning('Webhook HMAC validation failed');
            return response()->json(['error' => 'HMAC validation failed'], 403);
        }

        return $next($request);
    }
}
