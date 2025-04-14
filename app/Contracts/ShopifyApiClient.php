<?php

namespace App\Contracts;

use App\Data\ShopifyResponseData;

interface ShopifyApiClient
{
    /**
     * Make a GET request to the Shopify API.
     *
     * @param string $endpoint The API endpoint (e.g., '/admin/api/2023-10/products.json')
     * @param array $params Query parameters
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function get(string $endpoint, array $params = [], ?string $accessToken = null): ShopifyResponseData;

    /**
     * Make a POST request to the Shopify API.
     *
     * @param string $endpoint The API endpoint
     * @param array $data Request payload
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function post(string $endpoint, array $data = [], ?string $accessToken = null): ShopifyResponseData;

    /**
     * Make a PUT request to the Shopify API.
     *
     * @param string $endpoint The API endpoint
     * @param array $data Request payload
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function put(string $endpoint, array $data = [], ?string $accessToken = null): ShopifyResponseData;

    /**
     * Make a DELETE request to the Shopify API.
     *
     * @param string $endpoint The API endpoint
     * @param array $params Query parameters
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function delete(string $endpoint, array $params = [], ?string $accessToken = null): ShopifyResponseData;

    /**
     * Get the authenticated shop's information.
     *
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function getShopInfo(?string $accessToken = null): ShopifyResponseData;

    /**
     * Get a customer by ID from Shopify.
     *
     * @param string $customerId The Shopify customer ID
     * @param string|null $accessToken The access token to use (overrides the one from config)
     * @return ShopifyResponseData
     */
    public function getCustomer(string $customerId, ?string $accessToken = null): ShopifyResponseData;
}
