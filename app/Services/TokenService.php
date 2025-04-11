<?php

namespace App\Services;

use App\Contracts\ImpersonationLogRepository;
use App\Models\User;
use App\Contracts\TokenServiceInterface;
use App\Data\TokenResponseData;
use App\Models\RefreshToken;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class TokenService implements TokenServiceInterface
{
    public function __construct(
        protected ImpersonationLogRepository $logRepository
    ) {}

    public function createTokenForUser(User $user, array $options): array
    {
        $tokenName = $options['name'] ?? 'impersonation';
        $tokenAbilities = $options['scopes'] ?? ['*'];
        $expiresAt = isset($options['expires_in']) ? now()->addSeconds($options['expires_in']) : null;

        // Create token with Sanctum
        $token = $user->createToken($tokenName, $tokenAbilities, $expiresAt);
        $plainTextToken = $token->plainTextToken;
        $accessTokenId = explode('|', $plainTextToken)[0];

        if (isset($options['impersonated_by'])) {
            $this->logRepository->create([
                'impersonator_id' => $options['impersonated_by'],
                'user_id' => $user->id,
                'token_id' => $accessTokenId,
                'expires_at' => $expiresAt ?? now()->addDay(),
            ]);
        }

        $idToken = $this->generateIdToken($user, $options);

        // Create refresh token if requested
        $refreshTokenData = null;
        $refreshTokenExpiresIn = null;

        if (isset($options['include_refresh_token']) && $options['include_refresh_token']) {
            $refreshTokenExpiresIn = $options['refresh_token_expires_in'] ?? 86400 * 30; // 30 days by default
            $refreshToken = $this->createRefreshToken($user, $accessTokenId, $options, $refreshTokenExpiresIn);
            $refreshTokenData = $refreshToken->token;
        }

        return TokenResponseData::from([
            'access_token' => $plainTextToken,
            'id_token' => $idToken,
            'token_type' => 'Bearer',
            'expires_in' => $options['expires_in'] ?? 900,
            'refresh_token' => $refreshTokenData,
            'refresh_token_expires_in' => $refreshTokenExpiresIn,
        ])->toArray();
    }

    /**
     * Create a personal access token for the user.
     */
    public function createPersonalAccessToken(User $user, string $name, array $scopes = []): array
    {
        // Create a personal access token with Sanctum
        $expiresIn = 3600 * 24 * 365; // 1 year for personal tokens
        $expiresAt = now()->addSeconds($expiresIn);
        $token = $user->createToken($name, $scopes, $expiresAt);
        $plainTextToken = $token->plainTextToken;
        $accessTokenId = explode('|', $plainTextToken)[0];

        // Generate ID token
        $idToken = $this->generateIdToken($user, [
            'client_id' => 'personal_access',
            'expires_in' => $expiresIn,
        ]);

        // Create refresh token with long expiry for personal tokens
        $refreshTokenExpiresIn = 86400 * 365; // 365 days for personal tokens
        $refreshToken = $this->createRefreshToken(
            $user,
            $accessTokenId,
            [
                'client_id' => 'personal_access',
                'scopes' => $scopes,
                'token_name' => $name,
            ],
            $refreshTokenExpiresIn
        );

        return TokenResponseData::from([
            'access_token' => $plainTextToken,
            'id_token' => $idToken,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'refresh_token' => $refreshToken->token,
            'refresh_token_expires_in' => $refreshTokenExpiresIn,
        ])->toArray();
    }

    /**
     * Refresh an access token using a refresh token.
     */
    public function refreshToken(string $refreshToken): array
    {
        // Find the refresh token
        $token = RefreshToken::where('token', $refreshToken)
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$token) {
            throw new \Exception('Invalid or expired refresh token.');
        }

        // Get the user
        $user = $token->user;

        // Revoke the old refresh token
        $token->revoke();

        // Create a new token with the same scopes
        $options = [
            'scopes' => $token->scopes ?? [],
            'client_id' => $token->client_id,
            'include_refresh_token' => true,
            'refresh_token_expires_in' => Carbon::now()->diffInSeconds($token->expires_at),
        ];

        // Return a new token pair
        return $this->createTokenForUser($user, $options);
    }

    /**
     * Revoke a refresh token.
     */
    public function revokeToken(string $refreshToken): bool
    {
        $token = RefreshToken::where('token', $refreshToken)->first();

        if (!$token) {
            return false;
        }

        return $token->revoke();
    }

    /**
     * List user's personal access tokens.
     */
    public function listUserTokens(User $user): array
    {
        $tokens = $user->refreshTokens()
            ->where('client_id', 'personal_access')
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->scopes['token_name'] ?? 'Unnamed Token',
                    'scopes' => $token->scopes['scopes'] ?? [],
                    'created_at' => $token->created_at->toDateTimeString(),
                    'expires_at' => $token->expires_at->toDateTimeString(),
                ];
            })
            ->toArray();

        return $tokens;
    }

    protected function generateIdToken(User $user, array $options): string
    {
        $now = time();
        $exp = $now + ($options['expires_in'] ?? 900);

        $payload = [
            'iss' => config('app.url'),
            'sub' => (string) $user->id,
            'aud' => $options['client_id'] ?? 'shopify',
            'iat' => $now,
            'exp' => $exp,
            'email' => $user->email,
            'name' => $user->name,
        ];

        $privateKey = file_get_contents(base_path(env('OIDC_JWT_PRIVATE_KEY_PATH')));
        $kid = env('OIDC_JWT_KID', '1');

        return JWT::encode($payload, $privateKey, 'RS256', $kid);
    }

    /**
     * Create a refresh token for the user.
     */
    protected function createRefreshToken(User $user, string $accessTokenId, array $options, int $expiresIn): RefreshToken
    {
        return RefreshToken::create([
            'token' => Str::random(80),
            'user_id' => $user->id,
            'access_token_id' => $accessTokenId,
            'revoked' => false,
            'expires_at' => now()->addSeconds($expiresIn),
            'scopes' => $options,
            'client_id' => $options['client_id'] ?? 'shopify',
        ]);
    }
}
