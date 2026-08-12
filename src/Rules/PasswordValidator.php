<?php
namespace Clicalmani\Validation\Rules;

class PasswordValidator extends StringValidator
{
    protected static string $argument = 'password';

    public function validate(mixed &$value) : bool
    {
        $value = $this->parseString($value);
        
        if ( @ $this->options['length'] && strlen($value) !== @ $this->options['length'] ) return false;
        
        if ( @ $this->options['min'] && strlen($value) < @ $this->options['min'] ) return false;

        if ( @ $this->options['max'] && strlen($value) > @ $this->options['max'] ) {
            $value = substr($value, 0, $this->options['max']);
        }

        return true;
    }
}
