<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class NumbersValidator extends Validator
{
    protected string $argument = 'number[]';

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        if (!is_array($value)) $value = explode(',', $value);
        
        $value = $this->parseArray( $value );
        
        foreach ($value as $entry) {
            if ( ! is_numeric($entry) ) return false;
        }

        return true;
    }
}
