<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'shopify' => [
        'client_id' => env('SHOPIFY_CLIENT_ID'),
        'client_secret' => env('SHOPIFY_CLIENT_SECRET'),
        'redirect_uri' => env('SHOPIFY_REDIRECT_URI'),
        'shop_domain' => env('SHOPIFY_SHOP_DOMAIN'),
        'authorization_endpoint' => env('SHOPIFY_AUTH_ENDPOINT', 'https://{shop}/admin/oauth/authorize'),
        'token_endpoint' => env('SHOPIFY_TOKEN_ENDPOINT', 'https://{shop}/admin/oauth/access_token'),
        'userinfo_endpoint' => env('SHOPIFY_USERINFO_ENDPOINT', 'https://{shop}/admin/oauth/userinfo'),
        'jwks_uri' => env('SHOPIFY_JWKS_URI'),
        'scopes' => env('SHOPIFY_SCOPES', 'openid email profile'),
        'public_key_path' => env('OIDC_JWT_PUBLIC_KEY_PATH', 'storage/oauth-public.key'),
        'private_key_path' => env('OIDC_JWT_PRIVATE_KEY_PATH', 'storage/oauth-private.key'),
        'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
        'api_version' => env('SHOPIFY_API_VERSION', '2023-10'),
        'default_access_token' => env('SHOPIFY_DEFAULT_ACCESS_TOKEN'),
        'api_rate_limit_calls' => env('SHOPIFY_API_RATE_LIMIT_CALLS', 2),
        'api_rate_limit_seconds' => env('SHOPIFY_API_RATE_LIMIT_SECONDS', 1),
    ],

];
