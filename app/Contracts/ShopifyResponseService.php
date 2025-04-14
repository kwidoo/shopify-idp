<?php

namespace App\Contracts;

use Illuminate\Http\Client\Response;
use Spatie\LaravelData\Data;

interface ShopifyResponseService
{
    /**
     * Transform Shopify API response into a normalized DTO
     *
     * @param Response $response The HTTP response from Shopify
     * @param string $dataClass The DTO class to transform the response to
     * @return Data
     */
    public function transformResponse(Response $response, string $dataClass): Data;

    /**
     * Handle errors from Shopify API responses
     *
     * @param Response $response The HTTP response from Shopify
     * @param bool $throwOnError Whether to throw an exception on error
     * @return array|null Error details or null if no error
     * @throws \App\Exceptions\ShopifyApiException When throwOnError is true and response has errors
     */
    public function handleErrors(Response $response, bool $throwOnError = true): ?array;

    /**
     * Get error messages from Shopify API response
     *
     * @param Response $response The HTTP response from Shopify
     * @return array
     */
    public function getErrorMessages(Response $response): array;

    /**
     * Check if the Shopify API response is successful
     *
     * @param Response $response The HTTP response from Shopify
     * @return bool
     */
    public function isSuccessful(Response $response): bool;
}
