<?php

namespace App\Services;

use App\Contracts\UserRepository;
use Illuminate\Support\Facades\Log;

class ShopifyWebhookService
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Process a webhook based on its topic
     *
     * @param string $topic
     * @param string $payload
     * @param string $shopDomain
     * @return void
     */
    public function processWebhook(string $topic, string $payload, string $shopDomain): void
    {
        $data = json_decode($payload, true);

        if (!$data) {
            Log::error('Failed to decode webhook payload', ['topic' => $topic]);
            return;
        }

        switch ($topic) {
            case 'customers/update':
                $this->handleCustomerUpdate($data, $shopDomain);
                break;

            case 'customers/delete':
                $this->handleCustomerDelete($data, $shopDomain);
                break;

            case 'app/uninstalled':
                $this->handleAppUninstalled($data, $shopDomain);
                break;

            case 'shop/update':
                $this->handleShopUpdate($data, $shopDomain);
                break;

            default:
                Log::info('Unhandled webhook topic', ['topic' => $topic]);
                break;
        }
    }

    /**
     * Handle customer update webhook
     *
     * @param array $data
     * @param string $shopDomain
     * @return void
     */
    protected function handleCustomerUpdate(array $data, string $shopDomain): void
    {
        $shopifyId = (string) $data['id'];

        $user = $this->userRepository->findByField('shopify_id', $shopifyId)->first();

        if (!$user) {
            Log::info('User not found for customer update webhook', ['shopify_id' => $shopifyId]);
            return;
        }

        // Update user metadata based on customer data
        $metadata = $user->shopify_metadata ?? [];

        // Update relevant metadata fields
        $metadata['shop_id'] = $data['shop_id'] ?? $metadata['shop_id'] ?? null;
        $metadata['given_name'] = $data['first_name'] ?? $metadata['given_name'] ?? null;
        $metadata['family_name'] = $data['last_name'] ?? $metadata['family_name'] ?? null;
        $metadata['updated_at'] = now()->toIso8601String();

        // If email was changed, update user email too
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $user->email = $data['email'];
        }

        $user->shopify_metadata = $metadata;
        $user->save();

        Log::info('User updated from webhook', ['shopify_id' => $shopifyId]);
    }

    /**
     * Handle customer delete webhook
     *
     * @param array $data
     * @param string $shopDomain
     * @return void
     */
    protected function handleCustomerDelete(array $data, string $shopDomain): void
    {
        $shopifyId = (string) $data['id'];

        $user = $this->userRepository->findByField('shopify_id', $shopifyId)->first();

        if (!$user) {
            Log::info('User not found for customer delete webhook', ['shopify_id' => $shopifyId]);
            return;
        }

        // Option 1: Delete the user
        // $user->delete();

        // Option 2: Mark as inactive (recommended approach)
        $metadata = $user->shopify_metadata ?? [];
        $metadata['deactivated'] = true;
        $metadata['deactivated_at'] = now()->toIso8601String();
        $user->shopify_metadata = $metadata;
        $user->save();

        Log::info('User marked as deactivated from webhook', ['shopify_id' => $shopifyId]);
    }

    /**
     * Handle app uninstalled webhook
     *
     * @param array $data
     * @param string $shopDomain
     * @return void
     */
    protected function handleAppUninstalled(array $data, string $shopDomain): void
    {
        // Mark all users from this shop as inactive
        $shopId = $data['id'] ?? null;

        if (!$shopId) {
            Log::error('Missing shop ID in app uninstalled webhook');
            return;
        }

        // Find all users with this shop_id in their metadata
        $users = $this->userRepository->findWhere([
            ['shopify_metadata->shop_id', '=', $shopId]
        ]);

        foreach ($users as $user) {
            $metadata = $user->shopify_metadata;
            $metadata['shop_deactivated'] = true;
            $metadata['shop_deactivated_at'] = now()->toIso8601String();
            $user->shopify_metadata = $metadata;
            $user->save();
        }

        Log::info('Marked users as deactivated due to app uninstall', [
            'shop_id' => $shopId,
            'user_count' => $users->count()
        ]);
    }

    /**
     * Handle shop update webhook
     *
     * @param array $data
     * @param string $shopDomain
     * @return void
     */
    protected function handleShopUpdate(array $data, string $shopDomain): void
    {
        $shopId = $data['id'] ?? null;

        if (!$shopId) {
            Log::error('Missing shop ID in shop update webhook');
            return;
        }

        // Find all users with this shop_id in their metadata
        $users = $this->userRepository->findWhere([
            ['shopify_metadata->shop_id', '=', $shopId]
        ]);

        // Update shop information for all related users
        foreach ($users as $user) {
            $metadata = $user->shopify_metadata;
            $metadata['shop_name'] = $data['name'] ?? $metadata['shop_name'] ?? null;
            $metadata['shop_domain'] = $shopDomain;
            $metadata['shop_updated_at'] = now()->toIso8601String();
            $user->shopify_metadata = $metadata;
            $user->save();
        }

        Log::info('Updated shop information for users', [
            'shop_id' => $shopId,
            'user_count' => $users->count()
        ]);
    }
}
