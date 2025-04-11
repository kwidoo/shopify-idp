<?php

namespace Tests\Feature;

use App\Contracts\OIDCClientServiceInterface;
use App\Contracts\UserProvisioningServiceInterface;
use App\Exceptions\OIDCException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Tests\TestCase;
use Mockery;

class OIDCAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Session::start();
    }

    public function testRedirectToShopifyReturnsCorrectAuthorizationUrl()
    {
        $response = $this->get('/auth/shopify');
        $response->assertStatus(302);
        $this->assertStringContainsString(config('services.shopify.authorization_endpoint'), $response->getTargetUrl());

        // Verify state is stored in session
        $this->assertTrue(Session::has('oidc_state'));
    }

    public function testCallbackWithValidCodeLogsUserIn()
    {
        // Mock the OIDC service
        $this->mock(OIDCClientServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getTokensFromAuthorizationCode')
                ->once()
                ->andReturn([
                    'access_token' => 'fake-access-token',
                    'id_token' => 'fake-id-token'
                ]);

            $mock->shouldReceive('validateIdToken')
                ->once()
                ->andReturn([
                    'sub' => '123456',
                    'email' => 'user@example.com',
                    'name' => 'Test User'
                ]);
        });

        // Set session state for verification
        $state = Str::random(40);
        Session::put('oidc_state', $state);

        $response = $this->get('/auth/shopify/callback?code=valid-code&state=' . $state);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function testCallbackWithInvalidStateReturnsError()
    {
        Session::put('oidc_state', 'correct-state');

        $response = $this->get('/auth/shopify/callback?code=valid-code&state=wrong-state');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('message');
        $this->assertGuest();
    }

    public function testCallbackWithInvalidTokenThrowsException()
    {
        $this->mock(OIDCClientServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getTokensFromAuthorizationCode')
                ->once()
                ->andReturn([
                    'access_token' => 'fake-access-token',
                    'id_token' => 'fake-id-token'
                ]);

            $mock->shouldReceive('validateIdToken')
                ->once()
                ->andThrow(new OIDCException('invalid_token', 'Token validation failed'));
        });

        $state = Str::random(40);
        Session::put('oidc_state', $state);

        $response = $this->get('/auth/shopify/callback?code=valid-code&state=' . $state);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('message');
        $this->assertGuest();
    }

    public function testExistingUserWithShopifyIdIsUpdatedAndLoggedIn()
    {
        // Create a user with a Shopify ID
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'shopify_id' => '123456',
            'shopify_metadata' => ['previous' => 'data'],
        ]);

        // Set up mocks
        $this->mock(OIDCClientServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getTokensFromAuthorizationCode')
                ->once()
                ->andReturn([
                    'access_token' => 'fake-access-token',
                    'id_token' => 'fake-id-token'
                ]);

            $mock->shouldReceive('validateIdToken')
                ->once()
                ->andReturn([
                    'sub' => '123456',
                    'email' => 'existing@example.com',
                    'name' => 'Updated Name',
                    'locale' => 'en-US'
                ]);
        });

        $state = Str::random(40);
        Session::put('oidc_state', $state);

        $response = $this->get('/auth/shopify/callback?code=valid-code&state=' . $state);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Check if user was properly updated
        $updatedUser = User::find($existingUser->id);
        $this->assertEquals('Updated Name', $updatedUser->name);
        $this->assertArrayHasKey('locale', $updatedUser->shopify_metadata);
        $this->assertEquals('en-US', $updatedUser->shopify_metadata['locale']);
    }

    public function testNewUserIsCreatedAndLoggedInIfNotExisting()
    {
        // Make sure we don't have the user in the database
        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);

        // Set up mocks
        $this->mock(OIDCClientServiceInterface::class, function ($mock) {
            $mock->shouldReceive('getTokensFromAuthorizationCode')
                ->once()
                ->andReturn([
                    'access_token' => 'fake-access-token',
                    'id_token' => 'fake-id-token'
                ]);

            $mock->shouldReceive('validateIdToken')
                ->once()
                ->andReturn([
                    'sub' => '999999',
                    'email' => 'new@example.com',
                    'name' => 'New User',
                    'shop_id' => '12345',
                    'shop_name' => 'Test Shop'
                ]);
        });

        $state = Str::random(40);
        Session::put('oidc_state', $state);

        $response = $this->get('/auth/shopify/callback?code=valid-code&state=' . $state);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Check if a new user was created properly
        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'name' => 'New User',
            'shopify_id' => '999999'
        ]);

        $newUser = User::where('email', 'new@example.com')->first();
        $this->assertEquals('Test Shop', $newUser->shopify_metadata['shop_name']);
    }
}
