<?php

namespace App\Contracts;

use App\Models\User;

interface TokenServiceInterface
{
    /**
     * Create access token and ID token for the user.
     */
    public function createTokenForUser(User $user, array $options): array;

    /**
     * Create a personal access token for the user.
     */
    public function createPersonalAccessToken(User $user, string $name, array $scopes = []): array;

    /**
     * Refresh an access token using a refresh token.
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * Revoke a refresh token.
     */
    public function revokeToken(string $refreshToken): bool;

    /**
     * List user's personal access tokens.
     */
    public function listUserTokens(User $user): array;
}
