<?php
namespace Clicalmani\Validation\Rules;

class NotInValidator extends InValidator
{
    protected static string $argument = 'not_in';

    public function validate(mixed &$value): bool
    {
        return !parent::validate($value);
    }
}
