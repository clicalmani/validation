<?php
namespace Clicalmani\Validation\Rules;

class JsonsValidator extends JsonValidator
{
    protected static string $argument = 'json[]';

    public function validate(mixed &$value) : bool
    {
        $this->cast($value, 'string');

        $value = json_decode($value, @$this->options['assoc'], @$this->options['depth'] ?? 512);
        
        if ( JSON_ERROR_NONE !== json_last_error() OR !is_iterable($value) ) return false;

        foreach ($value as $obj) {
            if (FALSE === parent::validate($value)) return false;
        }

        return true;
    }
}
