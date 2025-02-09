<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class UriValidator extends Validator
{
    protected string $argument = 'uri';

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        return !! filter_var($value, FILTER_VALIDATE_URL);
    }
}
