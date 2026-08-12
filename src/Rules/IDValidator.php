<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

class IDValidator extends Rule
{
    protected static string $argument = 'id';

    protected string $model;

    protected $primaryKey;

    public function options() : array
    {
        return [
            'model' => [
                'required' => true,
                'type' => 'string',
                'function' => fn(string $model) => collection(explode('_', $model))->map(fn(string $part) => ucfirst($part))->join('')
            ],
            'primary' => [
                'required' => false,
                'type' => 'string',
                'function' => function(string $primary) {
                    if ( strpos($primary, ',') ) $primary = explode(',', $primary);
                    return $primary;
                }
            ]
        ];
    }

    public function validate(mixed &$value) : bool
    {
        if (!$value) return false;
        
        $this->model = trim("\\App\\Models\\" . $this->options['model']);
        /** @var \Clicalmani\Database\Factory\Models\Model */
        $instance = $this->model::find($value);
        $this->primaryKey = @ $this->options['primary'] ? $this->options['primary']: $instance?->getKey();
        
        if ( class_exists($this->model) && $this->primaryKey ) {
            
            if ( is_array($this->primaryKey) ) $value = explode(',', $value);
            
            if (NULL === $this->model::where("$this->primaryKey = :key", ['key' => $value])->first()) return false;
            
            return true;  
        }

        return false;
    }
}
