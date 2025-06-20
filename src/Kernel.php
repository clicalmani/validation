<?php
namespace Clicalmani\Validation;

class Kernel {

    /**
     * Return input validators
     * 
     * @return string[]
     */
    public function validators() : array
    {
        return [
            \Clicalmani\Validation\Validators\BoolValidator::class,
            \Clicalmani\Validation\Validators\BooleanValidator::class,
            \Clicalmani\Validation\Validators\DateTimeValidator::class,
            \Clicalmani\Validation\Validators\DateValidator::class,
            \Clicalmani\Validation\Validators\EmailValidator::class,
            \Clicalmani\Validation\Validators\EnumValidator::class,
            \Clicalmani\Validation\Validators\InValidator::class,
            \Clicalmani\Validation\Validators\FloatValidator::class,
            \Clicalmani\Validation\Validators\IDValidator::class,
            \Clicalmani\Validation\Validators\IDsValidator::class,
            \Clicalmani\Validation\Validators\IntValidator::class,
            \Clicalmani\Validation\Validators\IntegerValidator::class,
            \Clicalmani\Validation\Validators\NumberValidator::class,
            \Clicalmani\Validation\Validators\NumbersValidator::class,
            \Clicalmani\Validation\Validators\NumericValidator::class,
            \Clicalmani\Validation\Validators\NumericsValidator::class,
            \Clicalmani\Validation\Validators\ObjectValidator::class,
            \Clicalmani\Validation\Validators\ObjectsValidator::class,
            \Clicalmani\Validation\Validators\RegExpValidator::class,
            \Clicalmani\Validation\Validators\StringValidator::class,
            \Clicalmani\Validation\Validators\StringsValidator::class,
            \Clicalmani\Validation\Validators\UriValidator::class,
            \Clicalmani\Validation\Validators\NavigationGuardValidator::class,
            \Clicalmani\Validation\Validators\PasswordValidator::class,
            \Clicalmani\Validation\Validators\FileValidator::class,
            \Clicalmani\Validation\Validators\ImageValidator::class,
            \Clicalmani\Validation\Validators\PDFValidator::class,
        ];
    }
};
