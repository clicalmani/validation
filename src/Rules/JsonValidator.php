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
        if ( is_string($value) ) {
            $value = json_decode($value, @$this->options['assoc'], @$this->options['depth'] ?? 512);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = [];
            }
        }

        return true;
    }

    public function message() : string
    {
        return $this->error_message ?: "The {$this->parameter} must be a valid JSON string.";
    }
}
