<?php
namespace Clicalmani\Validation;

use Clicalmani\Foundation\Providers\ValidationServiceProvider;

class Validator
{
    const ERROR_WARNING = 0;
    const ERROR_SILENCE = 1;
    const ERROR_THROW = 2;

    private static ?int $error_level = null;

    public function __construct(int $error_level = self::ERROR_THROW)
    {
        static::$error_level = $error_level;
    }
    
    /**
     * Sanitize input
     * 
     * @param array &$inputs
     * @param array $patterns
     * @return bool
     */
    public function sanitize(array &$inputs, array $patterns) : bool
    {
        foreach ($patterns as $param => $pattern) {
            
            if (NULL === $rule = $this->getRule($pattern, $param)) continue;
            
            if ( array_key_exists($param, $inputs) ) {
                
                if ( $rule->isNullable() && $inputs[$param] == '' ) {
                    $inputs[$param] = null;
                    continue;
                }

                if ($argument = $rule->getArgument()) {
                    if (FALSE === ValidationServiceProvider::seemsValidator($argument)) {
                        $rule->log(sprintf("%s is not a valid validator rule argument; expected a valid validator rule argument, got %s", $argument, $argument));
                    }

                    if ($rule->checkOptions()) {
                        $value = $inputs[$param];
                        $success = $rule->validate($value);
                        
                        if ( false === $success ) {
                            $rule->log(sprintf("Parameter %s is not valid; expected a valid value for %s validation rule, got %s", $param, $argument, json_encode($value)));
                        }

                        $inputs[$param] = $value;
                    }
                }
            } else {
                if ( $rule->isRequired() ) {
                    if ( FALSE === $rule->isSometimes() ) {
                        $rule->log(sprintf("Parameter %s is required for %s validation rule, while using %s validation pattern.", $param, self::getArgument($pattern), $pattern));
                    }
                    else {
                        $inputs[$param] = null;
                        continue;
                    }
                }
            }
        }

        return true;
    }

    public static function getArguments(string $pattern)
    {
        return collection( explode('|', $pattern) )
                ->filter(fn(string $argument) => preg_match('/^[0-9a-z\[\]-_]+$/', $argument));
    }

    public static function getArgument(string $pattern) : ?string
    {
        return self::getArguments($pattern)
                ->filter(fn(string $argument) => ! in_array($argument, Rule::getDefaultArguments()))
                ->first();
    }

    public static function getArgumentOptions(string $pattern, ?string $argument = null) : array
    {
        $argument = $argument ?: self::getArgument($pattern);
        
        if ($argument) {
            $options = collection( explode('|', $pattern) )
                            ->filter(fn(string $arg) => ! in_array($arg, array_merge(Rule::getDefaultArguments(), [$argument])))
                            ->map(function(string $option) {
                                if ($pos = strpos($option, ':')) {
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

        return [];
    }

    public function getRule(string $pattern, string $param) : ?RuleInterface
    {
        $ruleClass = ValidationServiceProvider::getValidator(self::getArgument($pattern));
        return $ruleClass ? new $ruleClass(parameter: $param, pattern: $pattern) : null;
    }

    public static function setErrorLevel(int $error_level = self::ERROR_THROW) : void
    {
        static::$error_level = $error_level;
    }

    public static function getErrorLevel() : int
    {
        return static::$error_level;
    }
}
