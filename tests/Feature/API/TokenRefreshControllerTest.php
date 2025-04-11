<?php

namespace Tests\Feature\API;

use App\Contracts\TokenServiceInterface;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class TokenRefreshControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testRefreshTokenEndpointReturnsNewTokens()
    {
        // Mock the token service
        $mockTokenService = Mockery::mock(TokenServiceInterface::class);
        $mockTokenService->shouldReceive('refreshToken')
            ->once()
            ->with('test-refresh-token')
            ->andReturn([
                'access_token' => 'new-access-token',
                'id_token' => 'new-id-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'new-refresh-token',
                'refresh_token_expires_in' => 86400,
            ]);

        $this->app->instance(TokenServiceInterface::class, $mockTokenService);

        $response = $this->postJson('/api/tokens/refresh', [
            'refresh_token' => 'test-refresh-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'access_token' => 'new-access-token',
                'id_token' => 'new-id-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'new-refresh-token',
                'refresh_token_expires_in' => 86400,
            ]);
    }

    public function testRefreshTokenFailsWithInvalidToken()
    {
        // Mock the token service to throw an exception
        $mockTokenService = Mockery::mock(TokenServiceInterface::class);
        $mockTokenService->shouldReceive('refreshToken')
            ->once()
            ->with('invalid-token')
            ->andThrow(new \Exception('Invalid or expired refresh token.'));

        $this->app->instance(TokenServiceInterface::class, $mockTokenService);

        $response = $this->postJson('/api/tokens/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid or expired refresh token.',
            ]);
    }

    public function testRevokeTokenEndpointSucceeds()
    {
        // Mock the token service
        $mockTokenService = Mockery::mock(TokenServiceInterface::class);
        $mockTokenService->shouldReceive('revokeToken')
            ->once()
            ->with('test-refresh-token')
            ->andReturn(true);

        $this->app->instance(TokenServiceInterface::class, $mockTokenService);

        $response = $this->postJson('/api/tokens/revoke', [
            'refresh_token' => 'test-refresh-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Token revoked successfully',
            ]);
    }

    public function testRevokeTokenFailsWithNonexistentToken()
    {
        // Mock the token service
        $mockTokenService = Mockery::mock(TokenServiceInterface::class);
        $mockTokenService->shouldReceive('revokeToken')
            ->once()
            ->with('nonexistent-token')
            ->andReturn(false);

        $this->app->instance(TokenServiceInterface::class, $mockTokenService);

        $response = $this->postJson('/api/tokens/revoke', [
            'refresh_token' => 'nonexistent-token',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Token not found or already revoked',
            ]);
    }
}
