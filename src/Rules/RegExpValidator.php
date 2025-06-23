<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class RegExpValidator extends Rule
{
    protected static string $argument = 'regexp';

    public function options() : array
    {
        return [
            'pattern' => [
                'required' => true,
                'type' => 'string'
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        $this->cast($value, 'string');
        $pattern = $this->options['pattern'];

        return !! preg_match("/^$pattern$/", $value);
    }
}
