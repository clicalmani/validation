<?php
namespace Clicalmani\Validation;

use Clicalmani\Core\Http\Request;
use Clicalmani\Core\Providers\ValidationServiceProvider;

/**
 * Validator class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
class Validator
{
    const ERROR_WARNING = 0;
    const ERROR_SILENCE = 1;
    const ERROR_THROW = 2;

    private static ?int $error_level = null;
    private static array $passed = [];
    private static array $errors = [];

    private array $validatedInputs = [];

    public function __construct(int $error_level = self::ERROR_THROW)
    {
        static::$error_level = $error_level;
    }

    /**
     * Sanitize input
     *
     * Ordre appliqué à chaque paramètre :
     * absent -> nullable -> confirmed -> validation de la règle -> hash.
     * Toutes les erreurs sont collectées ; le retour est false s'il y en a au moins une.
     *
     * @param array &$inputs
     * @param array $patterns
     * @return bool
     */
    public function sanitize(array &$inputs, array $patterns) : bool
    {
        $valid = true;
        
        foreach ($patterns as $param => $pattern) {
            
            if (in_array($param, static::$passed) || NULL === $rule = $this->getRule($pattern, $param)) continue;
            
            // 1. Paramètre absent
            if (!array_key_exists($param, $inputs)) {
                if ($rule->isRequired()) {
                    if ($rule->isSometimes()) {
                        $inputs[$param] = null;
                    } else {
                        $rule->log(sprintf("Parameter %s is required for %s validation rule, while using %s validation pattern.", $param, self::getArgument($pattern), $pattern));
                        $valid = false;
                    }
                }
                continue;
            }

            $value = $inputs[$param];
            
            // 2. nullable : null / chaîne vide => null ; sinon la valeur est validée normalement
            if ($rule->isNullable()) {
                if ($value === null) continue;

                if (is_string($value) && trim($value) === '') {
                    $inputs[$param] = null;
                    continue;
                }
            }

            // 3. confirmed (comparaison sur les valeurs en clair, donc avant hash)
            if ($rule->isConfirmed()) {
                $confirmed_param = $param . '_confirmation';

                if (!array_key_exists($confirmed_param, $inputs) || $value !== $inputs[$confirmed_param]) {
                    $rule->log(sprintf("The %s confirmation does not match.", $param));
                    $valid = false;
                    continue;
                }

                unset($inputs[$confirmed_param]);
            }
            
            // 4. Validation de la règle
            if ($rule->getArgument() && $rule->checkOptions()) {
                
                if (!$rule->validate($value)) {
                    // La règle a normalement déjà enregistré son message : pas de doublon.
                    if (empty(static::$errors[$param])) {
                        $rule->log(sprintf("Parameter %s is not valid; expected a valid value for %s validation rule, got %s", $param, $rule->getArgument(), json_encode($value)));
                    }
                    $valid = false;
                    continue;
                }
                
                if (Request::current()?->hasHeader('X-Inertia')) {
                    static::$passed[] = $param;
                    session("errors.$param")->remove();
                }

                $inputs[$param] = $value;
                $this->validatedInputs[$param] = $value;
            }

            // 5. hash : uniquement une fois la valeur validée
            if ($rule->isHash() && is_string($inputs[$param])) {
                $inputs[$param] = password(trim($inputs[$param]));
            }
        }

        return $valid;
    }

    public static function getArguments(string $pattern)
    {
        return collection( explode('|', $pattern) )
                ->filter(fn(string $argument) => preg_match('/^[a-z0-9_\[\]-]+$/', $argument));
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
                                if (($pos = strpos($option, ':')) !== false) {
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

    /**
     * Factory — crée et exécute une validation sur les inputs de la requête courante.
     * L'état statique (erreurs, paramètres déjà validés) est remis à zéro à chaque appel.
     *
     * @param  array $signatures  ['param' => 'rule|option:value|...']
     * @param  array $inputs      Données à valider (Request courant par défaut)
     * @param  int   $error_level Niveau d'erreur à appliquer
     * @return self
     */
    public static function make(
        array $signatures,
        array $inputs = [],
        int   $error_level = self::ERROR_SILENCE
    ): self
    {
        $instance = new self($error_level);
        static::$passed = [];
        static::$errors = [];

        $inputs ??= Request::current()?->all() ?? [];

        if (false === $instance->sanitize($inputs, $signatures)) {
            return $instance;
        }

        $instance->validatedInputs = $inputs;

        return $instance;
    }

    public static function hasErrors(): bool
    {
        return !empty(static::$errors);
    }

    public function fails(): bool
    {
        return static::hasErrors();
    }

    public function passes(): bool
    {
        return !static::hasErrors();
    }

    /**
     * Retourne les inputs après assainissement.
     */
    public function validated(): array
    {
        return $this->validatedInputs ?? [];
    }

    public static function setErrorLevel(int $error_level = self::ERROR_THROW) : void
    {
        static::$error_level = $error_level;
    }

    public static function getErrorLevel() : int
    {
        return static::$error_level ?? self::ERROR_THROW;
    }

    public static function passed(string $parameter) : void
    {
        static::$passed[] = $parameter;
    }

    public static function reset(): void
    {
        static::$passed = [];
        static::$errors = [];
        static::$error_level = null;
    }
}