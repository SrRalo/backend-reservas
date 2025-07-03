<?php


namespace App\Exceptions;

use Exception;

abstract class BusinessException extends Exception
{
    protected $statusCode = 400;
    protected $errorCode;
    protected $errorMessage;

    public function __construct(string $message = null, int $code = 0, Exception $previous = null)
    {
        $message = $message ?? $this->errorMessage ?? 'Business logic error';
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode ?? static::class;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $this->getErrorCode(),
                'message' => $this->getMessage(),
            ]
        ], $this->getStatusCode());
    }
}