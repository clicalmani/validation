<?php
namespace Clicalmani\Validation;

interface ValidatorInterface
{
    /**
     * Validate input
     * 
     * @param string &$value Value to validate
     * @param ?array $options Value options
     * @return bool
     */
    public function validate(mixed &$value, ?array $options = [] ) : bool;

    /**
     * Validator options
     * 
     * @return array
     */
    public function options() : array;
}
