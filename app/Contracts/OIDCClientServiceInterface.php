<?php

namespace App\Contracts;

interface OIDCClientServiceInterface
{
    public function validateIdToken(string $idToken): ?array;
    public function createAuthorizationUrl(array $options): string;
    public function getTokensFromAuthorizationCode(string $code): array;
}
