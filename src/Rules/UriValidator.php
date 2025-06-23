<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class UriValidator extends Rule
{
    protected static string $argument = 'uri';

    public function validate(mixed &$value) : bool
    {
        return !! filter_var($value, FILTER_VALIDATE_URL);
    }
}
