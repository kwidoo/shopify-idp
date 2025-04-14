<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ShopifyResponseData extends Data
{
    public function __construct(
        public bool $success,
        public ?array $data = null,
        public ?array $errors = null,
        public ?int $status = null,
        public ?string $message = null,
    ) {}

    /**
     * Create a success response
     *
     * @param array|null $data
     * @param int|null $status
     * @return self
     */
    public static function success(?array $data = null, ?int $status = 200): self
    {
        return new self(
            success: true,
            data: $data,
            status: $status
        );
    }

    /**
     * Create an error response
     *
     * @param array|null $errors
     * @param string|null $message
     * @param int|null $status
     * @return self
     */
    public static function error(?array $errors = null, ?string $message = null, ?int $status = 400): self
    {
        return new self(
            success: false,
            errors: $errors,
            status: $status,
            message: $message ?? 'Shopify API error'
        );
    }
}
