<?php
namespace Clicalmani\Validation\Validators;

class PDFValidator extends FileValidator
{
    protected string $argument = 'pdf';

    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        $is_file = parent::validate($value, $options);

        if (TRUE === $is_file) {
            /** @var \Clicalmani\Foundation\Http\Request */
            $request = \Clicalmani\Foundation\Http\Request::current();

            return 'pdf' === $request->file($this->parameter)->getClientExtension();
        }

        return false;
    }
}