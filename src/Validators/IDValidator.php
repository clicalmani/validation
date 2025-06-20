<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validator;

class IDValidator extends Validator
{
    protected string $argument = 'id';

    /**
     * ID model
     * 
     * @var string
     */
    protected string $model;

    /**
     * Model primary key
     * 
     * @var string|string[]
     */
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

    public function validate(mixed &$value, ?array $options = []) : bool
    {
        if (!$value) return false;
        
        $this->model = trim("\\App\\Models\\" . $options['model']);
        /** @var \Clicalmani\Database\Factory\Models\Model */
        $instance = $this->model::find($value);
        $this->primaryKey = @ $options['primary'] ? $options['primary']: $instance?->getKey();
        
        if ( class_exists($this->model) && $this->primaryKey ) {
            
            if ( is_array($this->primaryKey) ) $value = explode(',', $value);
            
            if (NULL === $this->model::where("$this->primaryKey = :key", ['key' => $value])->first()) return false;
            
            return true;  
        }

        return false;
    }
}
