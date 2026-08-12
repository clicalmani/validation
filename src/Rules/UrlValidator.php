<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class UrlValidator extends Rule
{
    protected static string $argument = 'url';

    public function validate(mixed &$value) : bool
    {
        return !! filter_var($value, FILTER_VALIDATE_URL);
    }
}
