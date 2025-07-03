<?php


namespace App\Exceptions;

class ValidationException extends BusinessException
{
    protected $statusCode = 422;
    protected $errorCode = 'VALIDATION_ERROR';
    protected $errorMessage = 'The given data was invalid';
}