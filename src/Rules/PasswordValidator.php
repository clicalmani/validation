<?php
namespace Clicalmani\Validation\Rules;

class PasswordValidator extends StringValidator
{
    protected static string $argument = 'password';

    public function options() : array
    {
        $this->options = parent::options();
        $this->options['confirmed'] = [
            'required' => false,
            'type' => 'bool'
        ];
        $this->options['hash'] = [
            'required' => false,
            'type' => 'bool'
        ];

        return $this->options;
    }

    public function validate(mixed &$value) : bool
    {
        $value = $this->parseString($value);

        if ( @ $this->options['length'] && strlen($value) !== @ $this->options['length'] ) return false;
        
        if ( @ $this->options['min'] && strlen($value) < @ $this->options['min'] ) return false;

        if ( @ $this->options['max'] && strlen($value) > @ $this->options['max'] ) {
            $value = substr($value, 0, $this->options['max']);
        }

        if ( @$this->options['confirmed'] && $value != request("{$this->parameter}_confirmation")) return false;

        if ( @$this->options['hash']) $value = password($value);

        return true;
    }
}
