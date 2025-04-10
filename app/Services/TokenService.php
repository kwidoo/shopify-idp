<?php

namespace App\Services;

use App\Models\User;
use App\Models\ImpersonationLog;
use Firebase\JWT\JWT;

class TokenService
{
    public function createTokenForUser(User $user, array $options): array
    {
        $accessToken = $user->createToken('impersonation', $options['scopes'] ?? [])->accessToken;

        ImpersonationLog::create(
            [
            'impersonator_id' => $options['impersonated_by'],
            'user_id' => $user->id,
            'token_id' => $accessToken->id,
            'expires_at' => now()->addSeconds($options['expires_in'] ?? 900),
            ]
        );

        $idToken = $this->generateIdToken($user, $options);

        return [
            'access_token' => $accessToken,
            'id_token' => $idToken,
            'token_type' => 'Bearer',
            'expires_in' => $options['expires_in'] ?? 900,
        ];
    }

    protected function generateIdToken(User $user, array $options): string
    {
        $now = time();
        $exp = $now + ($options['expires_in'] ?? 900);

        $payload = [
            'iss' => config('app.url'),
            'sub' => (string)$user->id,
            'aud' => $options['client_id'] ?? 'shopify', // Shopify client ID
            'iat' => $now,
            'exp' => $exp,
            'email' => $user->email,
            'name' => $user->name,
        ];

        $privateKey = file_get_contents(base_path(env('OIDC_JWT_PRIVATE_KEY_PATH')));
        $kid = env('OIDC_JWT_KID', '1');

        return JWT::encode($payload, $privateKey, 'RS256', $kid);
    }
}
