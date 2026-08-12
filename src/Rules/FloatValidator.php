<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class FloatValidator extends Rule
{
    protected static string $argument = 'float';

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
            'range' => [
                'required' => false,
                'type' => 'int'
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        $this->cast($value, 'float');

        if ( @ $this->options['min'] && $value < @ $this->options['min'] ) $value = $this->options['min'];

        if ( @ $this->options['max'] && $value > @ $this->options['max'] ) $value = $this->options['max'];

        if ( @ $this->options['range'] ) {
            @[$min, $max] = explode('-', $this->options['range']);

            if ( $value < $min || $value > $max ) return false;
        }

        return true;
    }
}
