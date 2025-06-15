<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validators\RegExpValidator as Validator;

class RegExpsValidator extends Validator
{
    /**
     * Validator argument
     * 
     * @var string
     */
    protected string $argument = "regexp[]";

    /**
     * Validate input
     * 
     * @param mixed &$value Input value
     * @param ?array $options Validator options
     * @return bool
     */
    public function validate(mixed &$values, ?array $options = [] ) : bool
    {
        $values = $this->parseArray($values);
        $pattern = $options['pattern'];

        foreach ($values as $value) {
            if (FALSE == preg_match("/^$pattern$/", $value)) return false;
        }

        return true;
    }
}
