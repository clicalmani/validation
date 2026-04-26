<?php
namespace Clicalmani\Validation\Rules;

class JsonsValidator extends JsonValidator
{
    protected static string $argument = 'json[]';

    private string $error_message = '';

    public function validate(mixed &$value) : bool
    {
        if ( !is_array($value) ) {
            $this->error_message = "The {$this->parameter} must be an array of JSON strings.";
            return false;
        }

        foreach ($value as $index => $data) {
            if ( !is_string($data) ) {
                $this->error_message = "Each item in {$this->parameter} must be a JSON string. Item at index {$index} is not a string.";
                return false;
            }
        }

        foreach ($value as $index =>$data) {
            if (FALSE === parent::validate($data)) return false;
            $value[$index] = $data;
        }
        
        return true;
    }

    public function message() : string
    {
        return $this->error_message ?: "The {$this->parameter} must be an array of valid JSON strings.";
    }
}
