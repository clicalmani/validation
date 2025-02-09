<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class BooleanValidator extends Validator
{
    protected string $argument = 'boolean';
    
    public function validate(mixed &$value, ?array $options = []) : bool
    {
        if ($value) $value = true;
        if (NULL === $value || (is_numeric($value) && $this->parseInt($value) === 0)) $value = false;
        
        return true;
    }
}
