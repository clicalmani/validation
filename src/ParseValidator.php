<?php
namespace Clicalmani\Validation;

trait ParseValidator
{
    public function getArguments()
    {
        return collection( explode('|', $this->signature) )
                ->filter(fn(string $argument) => preg_match('/^[0-9a-z\[\]-_]+$/', $argument));
    }

    public function getOptions()
    {
        $options = collection( explode('|', $this->signature) )
                        ->filter(fn(string $argument) => ! in_array($argument, array_merge($this->defaultArguments, [$this->argument])))
                        ->map(function(string $option) {
                            if (FALSE !== $pos = strpos($option, ':')) {
                                $opt = substr($option, 0, $pos);
                                $value = substr($option, $pos + 1);
                                return [$opt, $value];
                            }
                            return [$option, null];
                        });

        $ret = [];

        foreach ($options as $option) {
            $ret[$option[0]] = $option[1];
        }
        
        return $ret;
    }

    public function getArgumentOptions(string $name)
    {
        $this->argument = $name;

        if ( -1 !== $this->getArguments()->index($name) ) return $this->getOptions();

        return null;
    }
}
