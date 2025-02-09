<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class NumberValidator extends Validator
{
    protected string $argument = 'number';

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

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $value = $this->parseInt($value);

        if ( @ $options['min'] && $value < @ $options['min'] ) $value = $options['min'];

        if ( @ $options['max'] && $value > @ $options['max'] ) $value = $options['max'];

        if ( @ $options['range'] ) {
            @[$min, $max] = explode('-', $options['range']);
            if ( $value < $min || $value > $max ) return false;
        }

        return true;
    }
}
