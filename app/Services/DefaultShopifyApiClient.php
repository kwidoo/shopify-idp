<?php

namespace App\Services;

use App\Contracts\ShopifyApiClient;
use App\Data\ShopifyResponseData;
use App\Exceptions\ShopifyApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DefaultShopifyApiClient implements ShopifyApiClient
{
    private Client $httpClient;
    private string $shopDomain;
    private string $apiVersion;
    private ?string $defaultAccessToken;
    private ShopifyRateLimiter $rateLimiter;

    public function __construct(ShopifyRateLimiter $rateLimiter = null)
    {
        $this->shopDomain = config('services.shopify.shop_domain');
        $this->apiVersion = config('services.shopify.api_version', '2023-10');
        $this->defaultAccessToken = config('services.shopify.default_access_token');
        $this->rateLimiter = $rateLimiter ?? new ShopifyRateLimiter();

        $this->httpClient = new Client([
            'http_errors' => false,
            'timeout' => 30,
            'connect_timeout' => 10
        ]);
    }

    /**
     * Make a GET request to the Shopify API.
     *
     * @param string $endpoint The API endpoint (e.g., '/admin/products.json')
     * @param array $params Query parameters
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function get(string $endpoint, array $params = [], ?string $accessToken = null): ShopifyResponseData
    {
        return $this->makeRequest('GET', $endpoint, [], $params, $accessToken);
    }

    /**
     * Make a POST request to the Shopify API.
     *
     * @param string $endpoint The API endpoint
     * @param array $data Request payload
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function post(string $endpoint, array $data = [], ?string $accessToken = null): ShopifyResponseData
    {
        return $this->makeRequest('POST', $endpoint, $data, [], $accessToken);
    }

    /**
     * Make a PUT request to the Shopify API.
     *
     * @param string $endpoint The API endpoint
     * @param array $data Request payload
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function put(string $endpoint, array $data = [], ?string $accessToken = null): ShopifyResponseData
    {
        return $this->makeRequest('PUT', $endpoint, $data, [], $accessToken);
    }

    /**
     * Make a DELETE request to the Shopify API.
     *
     * @param string $endpoint The API endpoint
     * @param array $params Query parameters
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function delete(string $endpoint, array $params = [], ?string $accessToken = null): ShopifyResponseData
    {
        return $this->makeRequest('DELETE', $endpoint, [], $params, $accessToken);
    }

    /**
     * Get the authenticated shop's information.
     *
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function getShopInfo(?string $accessToken = null): ShopifyResponseData
    {
        $cacheKey = 'shopify_shop_info_' . ($accessToken ?? $this->defaultAccessToken);

        // Try to get from cache first
        if (Cache::has($cacheKey)) {
            return ShopifyResponseData::success(Cache::get($cacheKey));
        }

        $endpoint = '/admin/api/' . $this->apiVersion . '/shop.json';
        $response = $this->get($endpoint, [], $accessToken);

        if ($response->success && isset($response->data['shop'])) {
            // Cache shop info for 1 hour
            Cache::put($cacheKey, $response->data, now()->addHour());
        }

        return $response;
    }

    /**
     * Get a customer by ID from Shopify.
     *
     * @param string $customerId The Shopify customer ID
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function getCustomer(string $customerId, ?string $accessToken = null): ShopifyResponseData
    {
        $endpoint = '/admin/api/' . $this->apiVersion . '/customers/' . $customerId . '.json';
        return $this->get($endpoint, [], $accessToken);
    }

    /**
     * Make a request to the Shopify API.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint
     * @param array $data Request body data
     * @param array $params Query parameters
     * @param string|null $accessToken Access token (overrides the default one)
     * @return ShopifyResponseData
     */
    private function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $params = [],
        ?string $accessToken = null
    ): ShopifyResponseData {
        $token = $accessToken ?? $this->defaultAccessToken;

        if (!$token) {
            Log::error('Shopify API access token not provided');
            throw new ShopifyApiException('Access token is required for Shopify API requests');
        }

        // Ensure the endpoint has the proper format
        if (!str_starts_with($endpoint, '/')) {
            $endpoint = '/' . $endpoint;
        }

        // If the endpoint doesn't include /admin/api/{version}, add it
        if (!str_contains($endpoint, '/admin/api/')) {
            $endpoint = '/admin/api/' . $this->apiVersion . $endpoint;
        }

        // Build full URL
        $url = 'https://' . $this->shopDomain . $endpoint;

        // Apply rate limiting
        if ($this->rateLimiter->throttle($this->shopDomain, $endpoint)) {
            // If rate limit is hit, wait for reset
            $this->rateLimiter->waitForReset($this->shopDomain, $endpoint);
        }

        // Prepare request options
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Shopify-Access-Token' => $token
            ],
            'query' => $params
        ];

        // Add request body if needed
        if (!empty($data) && in_array($method, ['POST', 'PUT'])) {
            $options['json'] = $data;
        }

        try {
            $response = $this->httpClient->request($method, $url, $options);
            $statusCode = $response->getStatusCode();
            $contentType = $response->getHeaderLine('Content-Type');
            $body = $response->getBody()->getContents();

            // Check if response is JSON
            $isJson = str_contains($contentType, 'application/json');
            $responseData = $isJson ? json_decode($body, true) : null;

            // Capture API rate limit headers if present
            $this->captureRateLimitHeaders($response);

            // Handle response based on status code
            if ($statusCode >= 200 && $statusCode < 300) {
                return ShopifyResponseData::success($responseData, $statusCode);
            } else {
                $message = $isJson && isset($responseData['errors'])
                    ? json_encode($responseData['errors'])
                    : "Shopify API error: HTTP $statusCode";

                Log::error('Shopify API error', [
                    'status' => $statusCode,
                    'endpoint' => $endpoint,
                    'response' => $responseData
                ]);

                return ShopifyResponseData::error(
                    $responseData,
                    $message,
                    $statusCode
                );
            }
        } catch (GuzzleException $e) {
            Log::error('Shopify API request failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);

            return ShopifyResponseData::error(
                null,
                'Failed to connect to Shopify: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Capture and log API rate limit headers from Shopify response
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return void
     */
    private function captureRateLimitHeaders($response): void
    {
        $rateHeaders = [
            'X-Shopify-Shop-Api-Call-Limit' => null,
            'X-Shopify-API-Limit' => null,
        ];

        foreach ($rateHeaders as $header => $value) {
            if ($response->hasHeader($header)) {
                $rateHeaders[$header] = $response->getHeaderLine($header);

                // If we're approaching the limit (>80%), log a warning
                if ($header === 'X-Shopify-Shop-Api-Call-Limit') {
                    $parts = explode('/', $rateHeaders[$header]);
                    if (count($parts) === 2 && (intval($parts[0]) / intval($parts[1])) > 0.8) {
                        Log::warning('Approaching Shopify API rate limit', [
                            'limit' => $rateHeaders[$header],
                            'shop' => $this->shopDomain
                        ]);
                    }
                }
            }
        }
    }
}
