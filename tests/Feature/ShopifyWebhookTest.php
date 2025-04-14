<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ShopifyWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class ShopifyWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set a mock webhook secret for testing
        Config::set('services.shopify.webhook_secret', 'test_webhook_secret');
    }

    public function testWebhookRequiresValidHmac()
    {
        $payload = [
            'id' => '12345',
            'email' => 'updated@example.com'
        ];

        // Send a request without HMAC header
        $response = $this->withHeaders([
            'X-Shopify-Topic' => 'customers/update',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com'
        ])->postJson('/api/webhooks/shopify', $payload);

        $response->assertForbidden();

        // Send a request with invalid HMAC header
        $response = $this->withHeaders([
            'X-Shopify-Topic' => 'customers/update',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Hmac-SHA256' => 'invalid-hmac-signature'
        ])->postJson('/api/webhooks/shopify', $payload);

        $response->assertForbidden();
    }

    public function testCustomerUpdateWebhook()
    {
        // Create a test user
        $user = User::factory()->create([
            'shopify_id' => '12345',
            'email' => 'original@example.com',
            'shopify_metadata' => [
                'shop_id' => '67890',
                'given_name' => 'Original'
            ]
        ]);

        // Prepare test webhook payload
        $payload = [
            'id' => 12345,
            'email' => 'updated@example.com',
            'first_name' => 'Updated',
            'shop_id' => '67890'
        ];

        // Calculate valid HMAC for the payload
        $hmac = base64_encode(hash_hmac('sha256', json_encode($payload), 'test_webhook_secret', true));

        // Send the webhook request
        $response = $this->withHeaders([
            'X-Shopify-Topic' => 'customers/update',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Hmac-SHA256' => $hmac
        ])->postJson('/api/webhooks/shopify', $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Process the webhook to verify database changes
        $service = app(ShopifyWebhookService::class);
        $service->processWebhook('customers/update', json_encode($payload), 'test-store.myshopify.com');

        // Verify user data was updated
        $updatedUser = User::find($user->id);
        $this->assertEquals('updated@example.com', $updatedUser->email);
        $this->assertEquals('Updated', $updatedUser->shopify_metadata['given_name']);
        $this->assertArrayHasKey('updated_at', $updatedUser->shopify_metadata);
    }

    public function testCustomerDeleteWebhook()
    {
        // Create a test user
        $user = User::factory()->create([
            'shopify_id' => '12345',
            'email' => 'delete@example.com',
            'shopify_metadata' => [
                'shop_id' => '67890',
            ]
        ]);

        // Prepare test webhook payload
        $payload = [
            'id' => 12345,
        ];

        // Calculate valid HMAC for the payload
        $hmac = base64_encode(hash_hmac('sha256', json_encode($payload), 'test_webhook_secret', true));

        // Send the webhook request
        $response = $this->withHeaders([
            'X-Shopify-Topic' => 'customers/delete',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Hmac-SHA256' => $hmac
        ])->postJson('/api/webhooks/shopify', $payload);

        $response->assertOk();

        // Process the webhook to verify database changes
        $service = app(ShopifyWebhookService::class);
        $service->processWebhook('customers/delete', json_encode($payload), 'test-store.myshopify.com');

        // Verify user was marked as deactivated but not deleted
        $deactivatedUser = User::find($user->id);
        $this->assertNotNull($deactivatedUser); // User still exists
        $this->assertTrue($deactivatedUser->shopify_metadata['deactivated']);
        $this->assertArrayHasKey('deactivated_at', $deactivatedUser->shopify_metadata);
    }

    public function testCustomerUpdateWithNonExistingUser()
    {
        // Prepare test webhook payload with non-existing user ID
        $payload = [
            'id' => 99999, // Non-existing ID
            'email' => 'nonexistent@example.com',
            'first_name' => 'NonExistent'
        ];

        // Calculate valid HMAC for the payload
        $hmac = base64_encode(hash_hmac('sha256', json_encode($payload), 'test_webhook_secret', true));

        // Send the webhook request
        $response = $this->withHeaders([
            'X-Shopify-Topic' => 'customers/update',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Hmac-SHA256' => $hmac
        ])->postJson('/api/webhooks/shopify', $payload);

        $response->assertOk(); // Still returns success even if user not found

        // Process should not fail even with non-existing user
        $service = app(ShopifyWebhookService::class);
        $service->processWebhook('customers/update', json_encode($payload), 'test-store.myshopify.com');

        // No assertions needed - we're just ensuring this doesn't throw an exception
    }

    public function testItHandlesAppUninstalledWebhook()
    {
        // Create some test users for the shop
        $users = User::factory()->count(3)->create([
            'shopify_metadata' => ['shop_id' => '67890']
        ]);

        // Prepare test webhook payload for app uninstalled
        $payload = [
            'id' => 67890,
            'name' => 'Test Shop'
        ];

        // Calculate valid HMAC for the payload
        $hmac = base64_encode(hash_hmac('sha256', json_encode($payload), 'test_webhook_secret', true));

        // Send the webhook request
        $response = $this->withHeaders([
            'X-Shopify-Topic' => 'app/uninstalled',
            'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
            'X-Shopify-Hmac-SHA256' => $hmac
        ])->postJson('/api/webhooks/shopify', $payload);

        $response->assertOk();

        // Process the webhook to verify database changes
        $service = app(ShopifyWebhookService::class);
        $service->processWebhook('app/uninstalled', json_encode($payload), 'test-store.myshopify.com');

        // Verify all users for this shop are marked as deactivated
        foreach ($users as $user) {
            $user->refresh();
            $this->assertArrayHasKey('shop_deactivated', $user->shopify_metadata);
            $this->assertTrue($user->shopify_metadata['shop_deactivated']);
            $this->assertArrayHasKey('shop_deactivated_at', $user->shopify_metadata);
        }
    }

    public function testShopUpdateWebhook()
    {
        // Create test users for the shop
        $users = User::factory()->count(2)->create([
            'shopify_metadata' => [
                'shop_id' => '67890',
                'shop_name' => 'Original Shop Name'
            ]
        ]);

        // Prepare test webhook payload
        $payload = [
            'id' => 67890,
            'name' => 'Updated Shop Name',
            'domain' => 'updated-shop.myshopify.com'
        ];

        // Calculate valid HMAC for the payload
        $hmac = base64_encode(hash_hmac('sha256', json_encode($payload), 'test_webhook_secret', true));

        // Send the webhook request
        $response = $this->withHeaders([
            'X-Shopify-Topic' => 'shop/update',
            'X-Shopify-Shop-Domain' => 'updated-shop.myshopify.com',
            'X-Shopify-Hmac-SHA256' => $hmac
        ])->postJson('/api/webhooks/shopify', $payload);

        $response->assertOk();

        // Process the webhook to verify database changes
        $service = app(ShopifyWebhookService::class);
        $service->processWebhook('shop/update', json_encode($payload), 'updated-shop.myshopify.com');

        // Verify shop information was updated for all users
        foreach ($users as $user) {
            $user->refresh();
            $this->assertEquals('Updated Shop Name', $user->shopify_metadata['shop_name']);
            $this->assertEquals('updated-shop.myshopify.com', $user->shopify_metadata['shop_domain']);
            $this->assertArrayHasKey('shop_updated_at', $user->shopify_metadata);
        }
    }

    public function testMalformedJsonPayload()
    {
        // With malformed JSON, we expect the HMAC validation to fail
        // since the body won't match the generated signature
        $invalidPayload = '{not-valid-json}';
        $hmac = base64_encode(hash_hmac('sha256', $invalidPayload, 'test_webhook_secret', true));

        try {
            // Send the webhook request with invalid JSON
            $response = $this->withHeaders([
                'X-Shopify-Topic' => 'customers/update',
                'X-Shopify-Shop-Domain' => 'test-store.myshopify.com',
                'X-Shopify-Hmac-SHA256' => $hmac
            ])->post('/api/webhooks/shopify', [$invalidPayload], ['Content-Type' => 'application/json']);

            // For malformed JSON, we now expect 403 since HMAC validation will likely fail
            $response->assertForbidden();

            // We can still test that the service handles malformed JSON gracefully
            $service = app(ShopifyWebhookService::class);
            $service->processWebhook('customers/update', $invalidPayload, 'test-store.myshopify.com');

            // Test passes if no exception is thrown
            $this->assertTrue(true);
        } catch (\Throwable $e) {
            $this->fail("Test should not throw exception, but got: " . $e->getMessage());
        }
    }

    public function testUnhandledWebhookTopic()
    {
        // Mock logging to verify info is logged
        Log::shouldReceive('info')
            ->once()
            ->with('Unhandled webhook topic', ['topic' => 'unhandled/topic']);

        $payload = json_encode(['id' => 12345]);

        $service = app(ShopifyWebhookService::class);
        $service->processWebhook('unhandled/topic', $payload, 'test-store.myshopify.com');
        // No assertions needed here since we're just checking it doesn't throw an exception
    }
}
