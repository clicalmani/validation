<?php
namespace Clicalmani\Validation\Rules;

class AlphaValidator extends StringValidator
{
    protected static string $argument = 'alpha';
    
    public function validate(mixed &$value) : bool
    {
        $value = $this->parseString($value);
        return !!preg_match('/[a-zA-Z]/', $value);
    }
}
