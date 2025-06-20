<?php
namespace Clicalmani\Validation\Validators;

class PDFValidator extends FileValidator
{
    protected string $argument = 'pdf';

    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        $is_file = parent::validate($value, $options);

        if (TRUE === $is_file) {
            /** @var \Clicalmani\Http\Request */
            $request = \Clicalmani\Http\Request::currentRequest();

            return 'pdf' === $request->file($this->parameter)->getClientOriginalExtension();
        }

        return false;
    }
}