<?php
namespace Clicalmani\Validation\Validators;

class NumericValidator extends NumberValidator
{
    protected string $argument = 'numeric';
    
    public function options() : array
    {
        return array_merge(parent::options(), ['length' => [
            'required' => false,
            'type' => 'integer',
            'validator' => fn($value) => !!intval($value)
        ]]);
    }

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        if (FALSE === parent::validate($value, $options)) return false;

        $length = @ $options['length'];

        if ($length && strlen($value) > $length) $value = substr($value, 0, $length);

        return true;
    }
}
