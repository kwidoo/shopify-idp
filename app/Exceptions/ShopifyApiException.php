<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class ShopifyApiException extends Exception
{
    protected ?array $errors;
    protected ?Response $response;

    public function __construct(
        string $message = 'Shopify API error',
        int $code = 0,
        ?array $errors = null,
        ?Response $response = null
    ) {
        parent::__construct($message, $code);
        $this->errors = $errors;
        $this->response = $response;
    }

    /**
     * Get the Shopify error details
     *
     * @return array|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }

    /**
     * Get the original HTTP response
     *
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Create exception from a response
     *
     * @param Response $response
     * @param string $message
     * @return self
     */
    public static function fromResponse(Response $response, ?string $message = null): self
    {
        $data = $response->json();
        $errors = $data['errors'] ?? null;
        $errorMessage = is_array($errors)
            ? implode('; ', array_map(fn($k, $v) => is_array($v) ? "$k: " . implode(', ', $v) : "$k: $v", array_keys($errors), array_values($errors)))
            : ($errors ?? 'Unknown Shopify API error');

        return new self(
            $message ?? $errorMessage,
            $response->status(),
            $errors,
            $response
        );
    }
}
