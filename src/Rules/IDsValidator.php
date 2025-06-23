<?php
namespace Clicalmani\Validation\Rules;

class IDsValidator extends IDValidator
{
    protected static string $argument = "id[]";

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

    public function validate(mixed &$ids) : bool
    {
        if (is_string($ids)) $ids = explode(',', $ids);
            
        foreach ($ids as $id) {
            if (false == parent::validate($id)) return false;
        }

        if (isset($this->options['join'])) $ids = join(',', $ids);
        
        return true;
    }
}
