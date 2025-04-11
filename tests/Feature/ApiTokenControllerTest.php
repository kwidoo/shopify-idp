<?php

namespace Tests\Feature;

use App\Contracts\TokenServiceInterface;
use App\Models\RefreshToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ApiTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var User
     */
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user for testing
        /** @var User */
        $this->user = User::factory()->create();
    }

    public function testIndexPageDisplaysTokenManagement()
    {
        // Mock the token service
        $mockTokenService = $this->mock(TokenServiceInterface::class);
        $mockTokenService->shouldReceive('listUserTokens')
            ->once()
            ->andReturn([
                [
                    'id' => 1,
                    'name' => 'Test Token',
                    'scopes' => ['read:profile', 'read:products'],
                    'created_at' => Carbon::now()->subHour()->toDateTimeString(),
                    'expires_at' => Carbon::now()->addDays(30)->toDateTimeString(),
                ],
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('api-tokens.index'));

        $response->assertStatus(200)
            ->assertInertia(
                fn(AssertableInertia $page) => $page
                    ->component('ApiTokens/Index')
                    ->has('tokens')
                    ->has('availableScopes')
                    ->where('tokens.0.name', 'Test Token')
            );
    }

    public function testCreateTokenStoresNewToken()
    {
        // Create token data
        $tokenData = [
            'name' => 'New API Token',
            'scopes' => ['read:profile', 'read:products'],
        ];

        // Mock token service
        $mockTokenService = $this->mock(TokenServiceInterface::class);
        $mockTokenService->shouldReceive('createPersonalAccessToken')
            ->once()
            ->with($this->user, 'New API Token', ['read:profile', 'read:products'])
            ->andReturn([
                'access_token' => 'fake-access-token',
                'id_token' => 'fake-id-token',
                'token_type' => 'Bearer',
                'expires_in' => 31536000,
                'refresh_token' => 'fake-refresh-token',
                'refresh_token_expires_in' => 31536000,
            ]);

        $response = $this->actingAs($this->user)
            ->post(route('api-tokens.store'), $tokenData);

        $response->assertStatus(302)
            ->assertRedirect(route('api-tokens.index'))
            ->assertSessionHas('token', 'fake-access-token')
            ->assertSessionHas('refresh_token', 'fake-refresh-token')
            ->assertSessionHas('status', 'API token created successfully.');
    }

    public function testDestroyTokenRevokesToken()
    {
        // Create a refresh token for the user
        $refreshToken = RefreshToken::create([
            'token' => 'test-token',
            'user_id' => $this->user->id,
            'access_token_id' => 'test-access-token-id',
            'revoked' => false,
            'expires_at' => Carbon::now()->addMonth(),
            'scopes' => ['token_name' => 'Test Token', 'scopes' => ['read:profile']],
            'client_id' => 'personal_access',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('api-tokens.destroy', $refreshToken->id));

        $response->assertStatus(302)
            ->assertRedirect(route('api-tokens.index'))
            ->assertSessionHas('status', 'API token revoked successfully.');

        // Check that the token is revoked
        $this->assertTrue(RefreshToken::find($refreshToken->id)->revoked);
    }

    public function testUserCannotRevokeOtherUsersTokens()
    {
        // Create another user
        $otherUser = User::factory()->create();

        // Create a refresh token for the other user
        $refreshToken = RefreshToken::create([
            'token' => 'other-user-token',
            'user_id' => $otherUser->id,
            'access_token_id' => 'test-access-token-id',
            'revoked' => false,
            'expires_at' => Carbon::now()->addMonth(),
            'scopes' => ['token_name' => 'Other User Token'],
            'client_id' => 'personal_access',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('api-tokens.destroy', $refreshToken->id));

        $response->assertStatus(403);

        // Check that the token is not revoked
        $this->assertFalse(RefreshToken::find($refreshToken->id)->revoked);
    }
}
