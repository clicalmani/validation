<?php
namespace Clicalmani\Validation\Rules;

/**
 * NotInValidator class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
class NotInValidator extends InValidator
{
    protected static string $argument = 'not_in';

    public function validate(mixed &$value): bool
    {
        $found = $this->matches($value);

        if ($found === null) {
            $this->addError("The provided value is invalid.");
            return false;
        }

        if ($found) {
            $this->addError("The value '" . (is_scalar($value) ? $value : json_encode($value)) . "' is not allowed.");
            return false;
        }

        $this->validatedValue = $value;
        return true;
    }
}
