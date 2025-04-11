<?php

namespace App\Services;

use App\Contracts\OIDCClientServiceInterface;
use App\Exceptions\OIDCException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OIDCClientService implements OIDCClientServiceInterface
{
    protected function getJwksByKid(string $kid): ?array
    {
        $jwksCache = Cache::remember('shopify_jwks', 3600, function () {
            $response = Http::get(config('services.shopify.jwks_uri'));
            return $response->json();
        });

        if (!isset($jwksCache['keys'])) {
            throw new OIDCException('jwks_invalid', 'JWKS endpoint returned invalid data');
        }

        foreach ($jwksCache['keys'] as $key) {
            if ($key['kid'] === $kid) {
                return $key;
            }
        }

        return null;
    }

    protected function getPublicKey(string $kid): string
    {
        return Cache::remember('oidc_public_key_' . $kid, 3600, function () use ($kid) {
            $jwk = $this->getJwksByKid($kid);
            return $this->jwkToPem($jwk);
        });
    }

    public function validateIdToken(string $idToken): ?array
    {
        try {
            $tokenParts = explode('.', $idToken);
            if (count($tokenParts) !== 3) {
                throw new OIDCException('invalid_token_format', 'Token is not in valid JWT format');
            }

            $header = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', $tokenParts[0]))), true);

            if (!isset($header['kid'])) {
                throw new OIDCException('missing_kid', 'Token is missing kid parameter in header');
            }

            $jwk = $this->getJwksByKid($header['kid']);
            if (!$jwk) {
                throw new OIDCException('unknown_kid', 'No matching key found for kid');
            }

            $pem = $this->jwkToPem($jwk);
            $decoded = JWT::decode($idToken, new Key($pem, $header['alg'] ?? 'RS256'));

            $payload = (array) $decoded;

            if ($payload['iss'] !== config('services.shopify.shop_domain')) {
                throw new OIDCException('invalid_issuer', 'Token issuer does not match expected value');
            }

            if ($payload['aud'] !== config('services.shopify.client_id')) {
                throw new OIDCException('invalid_audience', 'Token audience does not match client ID');
            }

            if (!isset($payload['nonce']) || $payload['nonce'] !== session('oidc_nonce')) {
                throw new OIDCException('invalid_nonce', 'Token nonce validation failed');
            }

            session()->forget('oidc_nonce');

            return $payload;
        } catch (ExpiredException $e) {
            throw new OIDCException('token_expired', 'The token has expired', 401);
        } catch (SignatureInvalidException $e) {
            throw new OIDCException('invalid_signature', 'Token signature validation failed', 401);
        } catch (OIDCException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new OIDCException('validation_failed', $e->getMessage(), 500);
        }
    }

    public function createAuthorizationUrl(array $options): string
    {
        $nonce = Str::random(32);
        session(['oidc_nonce' => $nonce]);

        $params = [
            'client_id' => config('services.shopify.client_id'),
            'redirect_uri' => config('services.shopify.redirect_uri'),
            'response_type' => 'code',
            'scope' => $options['scope'] ?? 'openid email profile',
            'state' => $options['state'] ?? Str::random(40),
            'nonce' => $nonce,
        ];

        return config('services.shopify.authorization_endpoint') . '?' . http_build_query($params);
    }

    public function getTokensFromAuthorizationCode(string $code): array
    {
        $response = Http::asForm()->post(config('services.shopify.token_endpoint'), [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.shopify.client_id'),
            'client_secret' => config('services.shopify.client_secret'),
            'redirect_uri' => config('services.shopify.redirect_uri'),
            'code' => $code,
        ]);

        return $response->json();
    }

    protected function jwkToPem(array $jwk): string
    {
        // Handle x5c certificate chain if available
        if (isset($jwk['x5c']) && !empty($jwk['x5c'][0])) {
            $key = '-----BEGIN PUBLIC KEY-----' . PHP_EOL;
            $key .= wordwrap($jwk['x5c'][0], 64, PHP_EOL, true) . PHP_EOL;
            $key .= '-----END PUBLIC KEY-----';

            return $key;
        }

        // Handle RSA keys with n (modulus) and e (exponent)
        if (isset($jwk['n']) && isset($jwk['e']) && $jwk['kty'] === 'RSA') {
            $modulus = $this->base64UrlDecode($jwk['n']);
            $exponent = $this->base64UrlDecode($jwk['e']);

            $modulusHex = bin2hex($modulus);
            $exponentHex = bin2hex($exponent);

            $modulus = pack('H*', $modulusHex);
            $exponent = pack('H*', $exponentHex);

            $publicKey = \openssl_pkey_new([
                'rsa' => [
                    'n' => $modulus,
                    'e' => $exponent,
                ]
            ]);

            $keyData = \openssl_pkey_get_details($publicKey);
            return $keyData['key'];
        }

        throw new OIDCException('invalid_jwk', 'JWK is missing required parameters for conversion to PEM');
    }

    protected function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
