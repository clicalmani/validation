<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class FileValidator extends Rule
{
    protected static string $argument = 'file';

    public function options() : array
    {
        return [
            'max' => [
                'required' => false,
                'type' => 'integer'
            ],
            'ext' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $ext) => explode(',', $ext)
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        $value = $this->parseArray($value);
        
        if ($value['error'] !== UPLOAD_ERR_OK) return false;
        if (isset($this->options['max']) && $value['size'] > $this->options['max']) return false;

        if (isset($this->options['ext'])) {
            if (!in_array(pathinfo($value['name'], PATHINFO_EXTENSION), $this->options['ext'])) return false;
        }

        return true;
    }
}
