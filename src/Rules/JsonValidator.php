<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class JsonValidator extends Rule
{
    protected static string $argument = 'json';

    public function options() : array
    {
        return [
            'assoc' => [
                'required' => false,
                'type' => 'boolean'
            ],
            'depth' => [
                'required' => false,
                'type' => 'int'
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        $this->cast($value, 'string');
        
        $value = json_decode($value, @$this->options['assoc'], @$this->options['depth'] ?? 512);
        
        if ( JSON_ERROR_NONE !== json_last_error() ) return false;

        return true;
    }
}
