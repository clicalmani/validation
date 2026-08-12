<?php
namespace Clicalmani\Validation\Rules;

class PDFValidator extends FileValidator
{
    protected static string $argument = 'pdf';

    public function validate(mixed &$value ) : bool
    {
        $is_file = parent::validate($value);

        if (TRUE === $is_file) {
            /** @var \Clicalmani\Http\Request */
            $request = \Clicalmani\Foundation\Http\Request::current();

            return 'pdf' === $request->file($this->parameter)->getClientOriginalExtension();
        }

        return false;
    }
}