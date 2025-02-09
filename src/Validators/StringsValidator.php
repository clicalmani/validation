<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class StringsValidator extends Validator
{
    protected string $argument = 'string[]';

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
        if (is_string($value)) $value = explode(',', $value);
        $value = $this->parseArray($value ?? []);

        foreach ($value as $index => $entry) {

            if ( @ $options['length'] && strlen($entry) !== @ $options['length'] ) return false;
        
            if ( @ $options['min'] && strlen($entry) < @ $options['min'] ) return false;

            if ( @ $options['max'] && strlen($entry) > @ $options['max'] ) {
                $value[$index] = substr($entry, 0, $options['max']);
            }

            if ( is_numeric($entry) ) return false;
        }

        return true;
    }
}
