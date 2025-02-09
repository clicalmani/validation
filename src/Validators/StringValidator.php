<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class StringValidator extends Validator
{
    protected string $argument = 'string';

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

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $value = $this->parseString( $value );

        if ( @ $options['length'] && strlen($value) !== @ $options['length'] ) return false;
        
        if ( @ $options['min'] && strlen($value) < @ $options['min'] ) return false;

        if ( @ $options['max'] && strlen($value) > @ $options['max'] ) {
            $value = substr($value, 0, $options['max']);
        }

        return true;
    }
}
