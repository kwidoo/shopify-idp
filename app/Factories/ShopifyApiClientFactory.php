<?php

namespace App\Factories;

use App\Contracts\ShopifyApiClient;
use Illuminate\Contracts\Container\Container;

class ShopifyApiClientFactory
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Create a new instance of ShopifyApiClient.
     *
     * @param string|null $shopDomain Optional shop domain to override config
     * @param string|null $accessToken Optional access token to use
     * @return ShopifyApiClient
     */
    public function create(?string $shopDomain = null, ?string $accessToken = null): ShopifyApiClient
    {
        $client = $this->container->make(ShopifyApiClient::class);

        // Further customization could be added here based on parameters

        return $client;
    }
}
