<?php
namespace Clicalmani\Validation\Rules;

class JsonsValidator extends JsonValidator
{
    protected static string $argument = 'json[]';

    private string $error_message = '';

    public function validate(mixed &$value) : bool
    {
        if ( is_string($value) ) {
            $value = json_decode($value, @$this->options['assoc'], @$this->options['depth'] ?? 512);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = [];
            }
        }

        foreach ($value as $index => $data) {
            if (FALSE !== parent::validate($data)) {
                $value[$index] = $data;
            }
        }

        if (count($value) === 1 && !$value[0]) $value = [];
        
        return true;
    }

    public function message() : string
    {
        return $this->error_message ?: "The {$this->parameter} must be an array of valid JSON strings.";
    }
}
