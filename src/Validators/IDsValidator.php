<?php
namespace Clicalmani\Validation\Validators;

use Clicalmani\Validation\Validators\IDValidator as Validator;

class IDsValidator extends Validator
{
    /**
     * Validator argument
     * 
     * @var string
     */
    protected string $argument = "id[]";

    /**
     * Validator options
     * 
     * @return array
     */
    public function options() : array
    {
        $options = parent::options();
        unset($options['translate']);

        $options['join'] = [
            'required' => false,
            'type' => 'bool'
        ];

        return $options;
    }

    /**
     * Validate input
     * 
     * @param mixed &$ids Input value
     * @param ?array $options Validator options
     * @return bool
     */
    public function validate(mixed &$ids, ?array $options = [] ) : bool
    {
        if (is_string($ids)) $ids = explode(',', $ids);
            
        foreach ($ids as $id) {
            if (false == parent::validate($id, $options)) return false;
        }

        if (@$options['join']) $ids = join(',', $ids);
        
        return true;
    }
}
