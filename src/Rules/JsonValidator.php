<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class JsonValidator extends Rule
{
    protected static string $argument = 'json';

    private string $error_message = '';

    public function options() : array
    {
        return [
            'assoc' => [
                'required' => false,
                'type' => 'bool'
            ],
            'depth' => [
                'required' => false,
                'type' => 'int'
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        if ( !is_string($value) ) {
            $this->error_message = "The {$this->parameter} must be a string.";
            return false;
        }

        $value = json_decode($value, @$this->options['assoc'], @$this->options['depth'] ?? 512);
        
        if ( JSON_ERROR_NONE !== json_last_error() ) {
            $this->error_message = "The {$this->parameter} must be a valid JSON string.";
            return false;
        }

        return true;
    }

    public function message() : string
    {
        return $this->error_message ?: "The {$this->parameter} must be a valid JSON string.";
    }
}
