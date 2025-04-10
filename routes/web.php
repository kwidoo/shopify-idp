<?php
/**
 * Laravel Routes
 *
 * This file contains the web routes for the application.
 * It defines endpoints for the OpenID Connect configuration
 * and standard web routes.
 *
 * @package Routes
 */

use Illuminate\Support\Facades\Route;

Route::get(
    '/', function () {
        return view('welcome');
    }
);
Route::get(
    '/.well-known/openid-configuration', fn () => response()->json(
        [
        'issuer' => config('app.url'),
        'authorization_endpoint' => url('/oauth/authorize'),
        'token_endpoint' => url('/oauth/token'),
        'userinfo_endpoint' => url('/api/userinfo'),
        'jwks_uri' => url('/.well-known/jwks.json'),
        'response_types_supported' => ['code'],
        'subject_types_supported' => ['public'],
        'id_token_signing_alg_values_supported' => ['RS256'],
        'scopes_supported' => ['openid', 'email', 'profile'],
        ]
    )
);
Route::get(
    '/.well-known/jwks.json', function () {
        $publicKey = file_get_contents(storage_path('oauth-public.key'));
        $details = openssl_pkey_get_details(openssl_pkey_get_public($publicKey));
        $modulus = rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '=');
        $exponent = rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '=');

        return response()->json(
            [
            'keys' => [[
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'n' => $modulus,
            'e' => $exponent,
            'kid' => '1',
            ]]
            ]
        );
    }
);
