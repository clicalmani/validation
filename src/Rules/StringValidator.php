<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class StringValidator extends Rule
{
    protected static string $argument = 'string';

    public function options() : array
    {
        return [
            'min' => [
                'required' => false,
                'type' => 'int'
            ],
            'max' => [
                'required' => false,
                'type' => 'int'
            ],
            'length' => [
                'required' => false,
                'type' => 'int'
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        $this->cast($value, 'string');

        if ( @ $this->options['length'] && strlen($value) !== @ $this->options['length'] ) return false;
        
        if ( @ $this->options['min'] && strlen($value) < @ $this->options['min'] ) return false;

        if ( @ $this->options['max'] && strlen($value) > @ $this->options['max'] ) {
            $value = substr($value, 0, $this->options['max']);
        }

        return true;
    }
}
