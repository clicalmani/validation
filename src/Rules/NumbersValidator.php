<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class NumbersValidator extends Rule
{
    protected static string $argument = 'number[]';

    public function validate(mixed &$value) : bool
    {
        if (!is_array($value)) $value = explode(',', $value);
        
        foreach ($value as $entry) {
            if ( ! is_numeric($entry) ) return false;
        }

        return true;
    }
}
