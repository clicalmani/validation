<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

/**
 * EmailValidator class
 * 
 * This class is responsible for validating email addresses.
 * It extends the base Validator class and provides an implementation
 * for the validate method to check if the given email is valid.
 * 
 * @package Clicalmani\Validation\Validators
 */
class EmailValidator extends Validator
{
    /**
     * Validator argument
     * 
     * @var string
     */
    protected string $argument = 'email';

    /**
     * Options for the email validator
     * 
     * This method returns an array of options that can be used to
     * configure the email validation process. The options include
     * the format of the email, which is required and must be a string.
     * A custom validator is also provided to check the format.
     * 
     * @return array
     */
    public function options() : array
    {
        return [
            'unique' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $model) => fn(string $model) => 
                                    collection(explode('_', $model))
                                        ->map(fn(string $part) => ucfirst($part))->join('')
            ],
            'id' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }
    
    public function validate(mixed &$email, ?array $options = []) : bool
    {
        if (!$email) return false;

        if (NULL !== $model = @$options['unique']) {
            /** @var \Clicalmani\Database\Factory\Models\Model */
            $instance = new $model;
            $primary_key = $instance?->getKey();
            if (NULL !== $id = @$options['id']) {
                $row = $model::where("$this->parameter = :email AND $primary_key <> :id", ['email' => $email, 'id' => $id])->first();
            } else $row = $model::where("$this->parameter = :email", ['email' => $email])->first();

            if (NULL !== $row) return false;
        }

        return !! filter_var($this->parseString($email), FILTER_VALIDATE_EMAIL);
    }
}
