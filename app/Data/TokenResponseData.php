<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TokenResponseData extends Data
{
    public function __construct(
        public string $access_token,
        public string $id_token,
        public string $token_type,
        public int $expires_in,
        public ?string $refresh_token = null,
        public ?int $refresh_token_expires_in = null,
    ) {}
}
