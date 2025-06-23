<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * EmailValidator class
 * 
 * This class is responsible for validating email addresses.
 * It extends the base Validator class and provides an implementation
 * for the validate method to check if the given email is valid.
 * 
 * @package Clicalmani\Validation\Validators
 */
class EmailValidator extends Rule
{
    protected static string $argument = 'email';

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
    
    public function validate(mixed &$email) : bool
    {
        $this->cast($email, 'string');
        
        if (!$email) return false;

        if (NULL !== $model = @$this->options['unique']) {
            /** @var \Clicalmani\Database\Factory\Models\Model */
            $instance = new $model;
            $primary_key = $instance?->getKey();
            if (NULL !== $id = @$this->options['id']) {
                $row = $model::where("$this->parameter = :email AND $primary_key <> :id", ['email' => $email, 'id' => $id])->first();
            } else $row = $model::where("$this->parameter = :email", ['email' => $email])->first();

            if (NULL !== $row) return false;
        }

        return !! filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
