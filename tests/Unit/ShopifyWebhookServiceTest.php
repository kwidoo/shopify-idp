<?php

namespace Tests\Unit;

use App\Contracts\UserRepository;
use App\Models\User;
use App\Services\ShopifyWebhookService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ShopifyWebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $userRepository;
    protected $webhookService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::factory()->create([
            'shopify_id' => '12345',
            'email' => 'test@example.com',
            'shopify_metadata' => [
                'shop_id' => '67890',
                'given_name' => 'Test',
                'family_name' => 'User'
            ]
        ]);

        $this->userRepository = $this->app->make(UserRepository::class);
        $this->webhookService = new ShopifyWebhookService($this->userRepository);
    }

    public function testHandleCustomerUpdate()
    {
        $payload = json_encode([
            'id' => 12345,
            'email' => 'updated@example.com',
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'shop_id' => '67890'
        ]);

        $this->webhookService->processWebhook('customers/update', $payload, 'test-store.myshopify.com');

        // Refresh user from database
        $this->user->refresh();

        // Verify user data was updated
        $this->assertEquals('updated@example.com', $this->user->email);
        $this->assertEquals('Updated', $this->user->shopify_metadata['given_name']);
        $this->assertEquals('Name', $this->user->shopify_metadata['family_name']);
    }

    public function testHandleCustomerDelete()
    {
        $payload = json_encode([
            'id' => 12345
        ]);

        $this->webhookService->processWebhook('customers/delete', $payload, 'test-store.myshopify.com');

        // Refresh user from database
        $this->user->refresh();

        // Verify user was marked as deactivated but not deleted
        $this->assertTrue($this->user->shopify_metadata['deactivated']);
        $this->assertArrayHasKey('deactivated_at', $this->user->shopify_metadata);
    }

    public function testHandleAppUninstalled()
    {
        // Create additional users for the same shop
        User::factory()->count(2)->create([
            'shopify_metadata' => ['shop_id' => '67890']
        ]);

        $payload = json_encode([
            'id' => 67890,
            'name' => 'Test Shop'
        ]);

        $this->webhookService->processWebhook('app/uninstalled', $payload, 'test-store.myshopify.com');

        // Get all users with this shop ID
        $users = User::whereRaw("JSON_EXTRACT(shopify_metadata, '$.shop_id') = ?", ['67890'])->get();

        // Verify all users have been marked as deactivated
        foreach ($users as $user) {
            $this->assertTrue($user->shopify_metadata['shop_deactivated']);
            $this->assertArrayHasKey('shop_deactivated_at', $user->shopify_metadata);
        }
    }

    public function testHandleShopUpdate()
    {
        // Create additional users for the same shop
        User::factory()->count(2)->create([
            'shopify_metadata' => ['shop_id' => '67890', 'shop_name' => 'Old Shop Name', 'shop_domain' => 'old-domain.myshopify.com']
        ]);

        $payload = json_encode([
            'id' => 67890,
            'shop_name' => 'Updated Shop Name'
        ]);

        $this->webhookService->processWebhook('shop/update', $payload, 'updated-domain.myshopify.com');

        // Get all users with this shop ID
        $users = User::whereRaw("JSON_EXTRACT(shopify_metadata, '$.shop_id') = ?", ['67890'])->get();

        // Verify shop info was updated for all users
        foreach ($users as $user) {
            $this->assertEquals('Updated Shop Name', $user->shopify_metadata['shop_name']);
            $this->assertEquals('updated-domain.myshopify.com', $user->shopify_metadata['shop_domain']);
        }
    }

    public function testFailingToDecodeJsonPayload()
    {
        // Invalid JSON payload should not throw an exception
        $this->webhookService->processWebhook('customers/update', '{invalid-json', 'test-store.myshopify.com');

        // No assertion needed - we're just ensuring it doesn't throw an exception
        $this->assertTrue(true);
    }

    public function testMissingShopIdInAppUninstalled()
    {
        // Missing shop ID in payload
        $payload = json_encode([
            'name' => 'Test Shop'
            // missing 'id' field
        ]);

        $this->webhookService->processWebhook('app/uninstalled', $payload, 'test-store.myshopify.com');

        // Verify original user is unchanged
        $this->user->refresh();
        $this->assertArrayNotHasKey('shop_deactivated', $this->user->shopify_metadata);
    }

    public function testMissingShopIdInShopUpdate()
    {
        // Missing shop ID in payload
        $payload = json_encode([
            'name' => 'Updated Shop Name'
            // missing 'id' field
        ]);

        $this->webhookService->processWebhook('shop/update', $payload, 'updated-domain.myshopify.com');

        // Verify original user is unchanged
        $this->user->refresh();
        $this->assertNotEquals('Updated Shop Name', $this->user->shopify_metadata['shop_name'] ?? null);
    }
}
