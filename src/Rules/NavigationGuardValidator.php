<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class NavigationGuardValidator extends Rule
{
    protected static string $argument = "nguard";

    public function options() : array
    {
        return [
            'uid' => [
                'required' => true
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        if ( $guard = \Clicalmani\Routing\Memory::getGuard($this->options['uid']) AND is_callable($guard['callback']) ) return $guard['callback']($value);

        return false;
    }
}
