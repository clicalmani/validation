<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class BooleanValidator extends Rule
{
    protected static string $argument = 'boolean';
    
    public function validate(mixed &$value) : bool
    {
        $this->cast((int)$value, 'int');
        if ($value) $value = true;
        if (NULL === $value || (is_numeric($value) && $value === 0)) $value = false;
        
        return true;
    }
}
