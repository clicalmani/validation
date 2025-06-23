<?php
namespace Clicalmani\Validation\Rules;

class NumericValidator extends NumberValidator
{
    protected static string $argument = 'numeric';
    
    public function options() : array
    {
        return array_merge(parent::options(), ['length' => [
            'required' => false,
            'type' => 'int',
            'validator' => fn($value) => !!intval($value)
        ]]);
    }

    public function validate(mixed &$value) : bool
    {
        if (FALSE === parent::validate($value)) return false;

        $length = @ $this->options['length'];

        if ($length && strlen($value) > $length) $value = substr($value, 0, $length);

        return true;
    }
}
