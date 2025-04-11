<?php
// Create a dedicated exception class
namespace App\Exceptions;

class OIDCException extends \Exception
{
    protected $error;
    protected $errorDescription;

    public function __construct(string $error, string $errorDescription = null, int $code = 0)
    {
        $this->error = $error;
        $this->errorDescription = $errorDescription;
        parent::__construct($errorDescription ?? $error, $code);
    }

    public function getOIDCError(): string
    {
        return $this->error;
    }

    public function getOIDCErrorDescription(): ?string
    {
        return $this->errorDescription;
    }
}
