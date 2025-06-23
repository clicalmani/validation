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
            \Clicalmani\Validation\Rules\BoolValidator::class,
            \Clicalmani\Validation\Rules\BooleanValidator::class,
            \Clicalmani\Validation\Rules\DateTimeValidator::class,
            \Clicalmani\Validation\Rules\DateValidator::class,
            \Clicalmani\Validation\Rules\EmailValidator::class,
            \Clicalmani\Validation\Rules\EnumValidator::class,
            \Clicalmani\Validation\Rules\InValidator::class,
            \Clicalmani\Validation\Rules\FloatValidator::class,
            \Clicalmani\Validation\Rules\IDValidator::class,
            \Clicalmani\Validation\Rules\IDsValidator::class,
            \Clicalmani\Validation\Rules\IntValidator::class,
            \Clicalmani\Validation\Rules\IntegerValidator::class,
            \Clicalmani\Validation\Rules\NumberValidator::class,
            \Clicalmani\Validation\Rules\NumbersValidator::class,
            \Clicalmani\Validation\Rules\NumericValidator::class,
            \Clicalmani\Validation\Rules\NumericsValidator::class,
            \Clicalmani\Validation\Rules\JsonValidator::class,
            \Clicalmani\Validation\Rules\JsonsValidator::class,
            \Clicalmani\Validation\Rules\RegExpValidator::class,
            \Clicalmani\Validation\Rules\StringValidator::class,
            \Clicalmani\Validation\Rules\StringsValidator::class,
            \Clicalmani\Validation\Rules\UriValidator::class,
            \Clicalmani\Validation\Rules\NavigationGuardValidator::class,
            \Clicalmani\Validation\Rules\PasswordValidator::class,
            \Clicalmani\Validation\Rules\FileValidator::class,
            \Clicalmani\Validation\Rules\ImageValidator::class,
            \Clicalmani\Validation\Rules\PDFValidator::class,
        ];
    }
};
