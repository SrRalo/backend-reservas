<?php

namespace App\Exceptions;

class EntityNotFoundException extends BusinessException
{
    protected $statusCode = 404;
    protected $errorCode = 'ENTITY_NOT_FOUND';
    protected $errorMessage = 'The requested entity was not found';
}