<?php
namespace Clicalmani\Validation;

use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Support\Facades\Log;
use Clicalmani\Validation\Exceptions\ValidationException;
use Inertia\Response;

class Rule extends InputParser implements RuleInterface
{
    protected array $options;

    /**
     * Validator argument
     * 
     * @var string
     */
    protected static string $argument = '';

    /**
     * Default validation arguments
     * 
     * @var string[]
     */
    const DEFAULT_ARGUMENTS = ['required', 'nullable', 'sometimes', 'hash', 'confirmed'];

    public function __construct(protected ?string $parameter = null, protected string $pattern = '')
    {
        //
    }

    /**
     * Gets the rule argument.
     * 
     * @return string
     */
    public static function getArgument() : string
    {
        return static::$argument;
    }

    public static function getDefaultArguments() : array
    {
        return static::DEFAULT_ARGUMENTS;
    }

    public function options(): array
    {
        return [
            // ...
        ];
    }

    public function validate(mixed &$value): bool
    {
        throw new ValidationException(
            sprintf("%s must override the validate method", $this::class)
        );
    }

    /**
     * Gets the custom error message.
     * 
     * @return string
     */
    public function message() : ?string
    {
        return null;
    }

    public function isRequired() : bool
    {
        return $this->hasArgument('required');
    }

    public function isNullable() : bool
    {
        return $this->hasArgument('nullable');
    }

    public function isSometimes() : bool
    {
        return $this->hasArgument('sometimes');
    }

    public function isHash() : bool
    {
        return $this->hasArgument('hash');
    }

    public function isConfirmed() : bool
    {
        return $this->hasArgument('confirmed');
    }

    private function hasArgument(string $argument) : bool
    {
        return Validator::getArguments($this->pattern)->contains($argument);
    }

    public function checkOptions() : bool
    {
        $old_error_level = Validator::getErrorLevel();
        Validator::setErrorLevel(Validator::ERROR_THROW); // Set validation error level to high
        $this->options = Validator::getArgumentOptions($this->pattern);
        $options = $this->options();
        
        foreach ($this->options as $key => $value) {
            if (!isset($options[$key])) {
                $this->log(sprintf("%s is not a valid %s rule option; expected [%s] options, got %s", $key, static::$argument, join(',', array_keys($options)), $key));
            }
        }
        
        foreach ($options as $key => $value) {
            if (isset($this->options[$key])) {
                $option = new RuleOption(
                    $key, 
                    $this->options[$key],
                    @ $value['required'],
                    @ $value['type'],
                    @ $value['keys'] ?? [],
                    @ $value['function'],
                    @ $value['validator']
                );
                
                try {
                    $option->validate();
                } catch (ValidationException $e) {
                    $this->log(sprintf($e->getMessage(), static::$argument));
                }
            }
        }
        
        Validator::setErrorLevel($old_error_level);

        return true;
    }

    public function log(?string $message = null) : void
    {
        $message = $this->message() ?: $message;

        if (\Clicalmani\Foundation\Http\Request::current()?->hasHeader('X-Inertia')) {
            \Inertia\ComponentData::addError($this->parameter, $message);
            die(back());
        } else {
            switch (Validator::getErrorLevel()) {
                case Validator::ERROR_SILENCE: ; break;
                case Validator::ERROR_WARNING: Log::warning($message, self::class, __LINE__); break;
                case Validator::ERROR_THROW: throw new ValidationException($message); break;
            }
        }
    }
}