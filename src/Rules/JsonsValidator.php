<?php
namespace Clicalmani\Validation\Rules;

class JsonsValidator extends JsonValidator
{
    protected static string $argument = 'json[]';

    public function validate(mixed &$value) : bool
    {
        $value = $this->cast($value, 'array');

        foreach ($value as $data) {
            if (FALSE === parent::validate($data)) return false;
        }

        return true;
    }
}
