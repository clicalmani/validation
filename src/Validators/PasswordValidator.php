<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validators\StringValidator as Validator;

class PasswordValidator extends Validator
{
    protected string $argument = 'password';

    public function options() : array
    {
        $options = parent::options();
        $options['confirm'] = [
            'required' => false,
            'type' => 'bool'
        ];
        $options['hash'] = [
            'required' => false,
            'type' => 'bool'
        ];

        return $options;
    }

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        $value = $this->parseString( $value );

        if ( @ $options['length'] && strlen($value) !== @ $options['length'] ) return false;
        
        if ( @ $options['min'] && strlen($value) < @ $options['min'] ) return false;

        if ( @ $options['max'] && strlen($value) > @ $options['max'] ) {
            $value = substr($value, 0, $options['max']);
        }

        if ( @$options['confirm'] && $value != request("{$this->parameter}_confirm")) return false;

        if ( @$options['hash']) $value = password($value);

        return true;
    }
}
