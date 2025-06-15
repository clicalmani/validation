<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validators\EmailValidator as BaseEmailValidator;

class EmailsValidator extends BaseEmailValidator
{
    /**
     * Validator argument
     * 
     * @var string
     */
    protected string $argument = "email[]";

    /**
     * Validator options
     * 
     * @return array
     */
    public function options() : array
    {
        $options = parent::options();
        unset($options['translate']);

        return array_merge($options, [
            'join' => [
                'required' => false,
                'type' => 'string'
            ]
        ]);
    }

    /**
     * Validate input
     * 
     * @param mixed &$value Input value
     * @param ?array $options Validator options
     * @return bool
     */
    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        $value = $this->parseArray($value);

        if (empty($value)) {
            return false;
        }

        foreach ($value as $email) {
            if (FALSE === parent::validate($email, $options)) {
                return false;
            }
        }

        if ( isset($options['join']) ) $value = join($options['join'], $value);

        return true;
    }
}
