<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class FloatValidator extends Validator
{
    protected string $argument = 'float';

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

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $value = $this->parseFloat($value);

        if ( @ $options['min'] && $value < @ $options['min'] ) $value = $options['min'];

        if ( @ $options['max'] && $value > @ $options['max'] ) $value = $options['max'];

        if ( @ $options['range'] ) {
            @[$min, $max] = explode('-', $options['range']);

            if ( $value < $min || $value > $max ) return false;
        }

        return true;
    }
}
