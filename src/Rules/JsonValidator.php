<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class JsonValidator extends Rule
{
    protected static string $argument = 'json';

    public function options() : array
    {
        return [
            'associative' => [
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
        
        if ( @ $this->options['translate'] ) $value = strtr($value, $this->options['translate']['from'], $this->options['translate']['to']);
        
        $value = json_decode($value, @$this->options['associative'], @$this->options['depth'] ?? 512);
        
        if ( JSON_ERROR_NONE !== json_last_error() ) return false;

        return true;
    }
}
