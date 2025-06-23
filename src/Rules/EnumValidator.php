<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class EnumValidator extends Rule
{
    protected static string $argument = 'enum';

    public function options() : array
    {
        return [
            'list' => [
                'required' => true,
                'type' => 'array'
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        $list = $this->options['list'];

        $this->cast($value, 'string');
        $this->cast($list, 'array');
        
        return !! in_array($value, $list);
    }
}
