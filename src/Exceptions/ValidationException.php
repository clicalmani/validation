<?php
namespace Clicalmani\Validation\Exceptions;

/**
 * ValidationException class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
class ValidationException extends \Exception
{
    public function __construct(string $message = '', private string $parameter = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getParameter()
    {
        return $this->parameter;
    }
}