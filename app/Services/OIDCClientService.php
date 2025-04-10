<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Log;

class OIDCClientService
{
    public function validateIdToken(string $idToken): ?array
    {
        try {
            $publicKey = file_get_contents(base_path(env('OIDC_JWT_PUBLIC_KEY_PATH')));

            $decoded = JWT::decode($idToken, new Key($publicKey, 'RS256'));

            $payload = (array) $decoded;

            // Optional: Check claims
            if ($payload['iss'] !== config('app.url')) {
                throw new \Exception('Invalid issuer');
            }

            if ($payload['aud'] !== 'shopify') {
                throw new \Exception('Invalid audience');
            }

            return $payload;
        } catch (ExpiredException $e) {
            Log::warning('ID token expired', ['error' => $e->getMessage()]);
        } catch (SignatureInvalidException $e) {
            Log::warning('Invalid token signature', ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::warning('ID token validation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
