<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class FileValidator extends Validator
{
    protected string $argument = 'file';

    public function options() : array
    {
        return [
            'max' => [
                'required' => false,
                'type' => 'integer'
            ],
            'extension' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $ext) => explode(',', $ext)
            ]
        ];
    }

    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        $value = $this->parseArray($value);
        
        if ($value['error'] !== UPLOAD_ERR_OK) return false;
        if (isset($options['max']) && $value['size'] > $options['max']) return false;

        if (isset($options['extension'])) {
            if (!in_array(pathinfo($value['name'], PATHINFO_EXTENSION), $options['extension'])) return false;
        }

        return true;
    }
}
