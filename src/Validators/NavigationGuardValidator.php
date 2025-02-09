<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator as Validator;

class NavigationGuardValidator extends Validator
{
    /**
     * Validator argument
     * 
     * @var string
     */
    protected string $argument = "nguard";

    /**
     * Validator options
     * 
     * @return array
     */
    public function options() : array
    {
        return [
            'uid' => [
                'required' => true
            ]
        ];
    }

    /**
     * Validate input
     * 
     * @param mixed &$value Input value
     * @param ?array $options Validator options
     * @return bool
     */
    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        if ( $guard = \Clicalmani\Routing\Memory::getGuard($options['uid']) AND is_callable($guard['callback']) ) return $guard['callback']($value);

        return false;
    }
}
