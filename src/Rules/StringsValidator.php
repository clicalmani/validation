<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class StringsValidator extends Rule
{
    protected static string $argument = 'string[]';

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
        if (is_string($value)) $value = explode(',', $value);

        foreach ($value as $index => $entry) {

            if ( @ $this->options['length'] && strlen($entry) !== @ $this->options['length'] ) return false;
        
            if ( @ $this->options['min'] && strlen($entry) < @ $this->options['min'] ) return false;

            if ( @ $this->options['max'] && strlen($entry) > @ $this->options['max'] ) {
                $value[$index] = substr($entry, 0, $this->options['max']);
            }

            if ( is_numeric($entry) ) return false;
        }

        return true;
    }
}
