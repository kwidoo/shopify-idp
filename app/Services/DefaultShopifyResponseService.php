<?php

namespace App\Services;

use App\Contracts\ShopifyResponseService;
use App\Data\ShopifyResponseData;
use App\Exceptions\ShopifyApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Creation\CreationContext;

class DefaultShopifyResponseService implements ShopifyResponseService
{
    /**
     * Transform Shopify API response into a normalized DTO
     *
     * @param Response $response The HTTP response from Shopify
     * @param string $dataClass The DTO class to transform the response to
     * @return Data
     */
    public function transformResponse(Response $response, string $dataClass): Data
    {
        // Check for API errors before transforming
        $this->handleErrors($response);

        // Extract the data from response
        $data = $response->json();

        // Create an instance of the specified DTO class using the response data
        return $dataClass::from($data, new CreationContext(true));
    }

    /**
     * Handle errors from Shopify API responses
     *
     * @param Response $response The HTTP response from Shopify
     * @param bool $throwOnError Whether to throw an exception on error
     * @return array|null Error details or null if no error
     * @throws ShopifyApiException When throwOnError is true and response has errors
     */
    public function handleErrors(Response $response, bool $throwOnError = true): ?array
    {
        if ($this->isSuccessful($response)) {
            return null;
        }

        $errors = $this->getErrorMessages($response);

        // Log the error details
        Log::error('Shopify API error', [
            'status' => $response->status(),
            'url' => $response->effectiveUri(),
            'errors' => $errors,
        ]);

        if ($throwOnError) {
            throw ShopifyApiException::fromResponse($response);
        }

        return $errors;
    }

    /**
     * Get error messages from Shopify API response
     *
     * @param Response $response The HTTP response from Shopify
     * @return array
     */
    public function getErrorMessages(Response $response): array
    {
        $data = $response->json();

        if (!$data || !is_array($data)) {
            return ['Unknown error with status code: ' . $response->status()];
        }

        // Handle different Shopify error formats
        if (isset($data['errors'])) {
            return is_array($data['errors']) ? $data['errors'] : ['errors' => $data['errors']];
        }

        if (isset($data['error'])) {
            return ['error' => $data['error']];
        }

        if (isset($data['error_description'])) {
            return ['error_description' => $data['error_description']];
        }

        return ['Unknown error format with status code: ' . $response->status()];
    }

    /**
     * Check if the Shopify API response is successful
     *
     * @param Response $response The HTTP response from Shopify
     * @return bool
     */
    public function isSuccessful(Response $response): bool
    {
        // Check HTTP status code is in 2xx range
        if (!$response->successful()) {
            return false;
        }

        // Check for errors in the response JSON
        $data = $response->json();

        if (is_array($data)) {
            // Shopify might include errors or error fields even with 2xx status
            return !isset($data['errors']) && !isset($data['error']);
        }

        // If the response isn't JSON or doesn't contain error indicators, consider it successful
        return true;
    }
}
