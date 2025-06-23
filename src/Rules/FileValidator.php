<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class FileValidator extends Rule
{
    protected static string $argument = 'file';

    public function options() : array
    {
        return [
            'max' => [
                'required' => false,
                'type' => 'int'
            ]
        ];
    }

    public function validate(mixed &$value ) : bool
    {
        /** @var \Clicalmani\Http\Request */
        $request = \Clicalmani\Foundation\Http\Request::current();

        if ($request->file($this->parameter)?->isValid()) return true;

        return false;
    }
}
