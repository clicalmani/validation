<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class NumberValidator extends Rule
{
    protected static string $argument = 'number';

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
                'type' => 'string',
                'validator' => fn(string $range) => !!preg_match('/^[0-9]+-[0-9]+$/', $range)
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        if (!preg_match('/[0-9\.]+/', $value)) return false;

        if ( @ $this->options['min'] && $value < @ $this->options['min'] ) $value = $this->options['min'];

        if ( @ $this->options['max'] && $value > @ $this->options['max'] ) $value = $this->options['max'];

        if ( @ $this->options['range'] ) {
            @[$min, $max] = explode('-', $this->options['range']);
            if ( $value < $min || $value > $max ) return false;
        }

        return true;
    }
}
