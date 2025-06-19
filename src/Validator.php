<?php
namespace Clicalmani\Validation;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Foundation\Http\Request;
use Clicalmani\Foundation\Providers\ValidationServiceProvider;

class Validator implements ValidatorInterface
{
    use InputParser;
    use ParseValidator;

    /**
     * Holds the validator signature.
     * 
     * @var string
     */
    protected string $signature;
    
    /**
     * Validator argument
     * 
     * @var string
     */
    protected string $argument;

    /**
     * Default validation arguments
     * 
     * @var string[]
     */
    private $defaultArguments = ['required', 'nullable', 'sometimes'];

    /**
     * @var array
     */
    private $validated = [];

    public function __construct(private ?bool $silent = false, protected ?string $parameter = null) {}
    
    public function validate(mixed &$value, ?array $options = [] ) : bool
    {
        $this->log("Must override %s::%s in %s at line %d.", __CLASS__, __METHOD__, $this::class, __LINE__);

        return true;
    }

    public function options() : array
    {
        return [
            // Options
        ];
    }

    /**
     * Argument getter
     * 
     * @return string
     */
    public function getArgument() : string
    {
        return $this->argument;
    }

    /**
     * Input value is required
     * 
     * @return bool
     */
    public function isRequired() : bool
    {
        if ( -1 !== $this->getArguments()->index('required') ) return true;
        return false;
    }

    /**
     * Input value is nullable
     * 
     * @return bool
     */
    public function isNullable() : bool
    {
        if ( -1 !== $this->getArguments()->index('nullable') ) return true;
        return false;
    }

    /**
     * Input value is sometimes required
     * 
     * @return bool
     */
    public function isSometimes() : bool
    {
        if (-1 !== $this->getArguments()->index('sometimes')) return true;
        return false;
    }

    /**
     * Sanitize input
     * 
     * @param array &$inputs
     * @param array $signatures
     * @return bool
     */
    public function sanitize(array &$inputs, array $signatures) : bool
    {
        foreach ($signatures as $param => $sig) {
            
            if ( in_array($param, $this->validated) ) continue;
            
            $this->signature = $sig;
            
            if ( $this->isRequired() ) {
                if ( ! array_key_exists($param, $inputs) ) {
                    if ( FALSE === $this->isSometimes() ) $this->log("Parameter $param is required.");
                    else {
                        $inputs[$param] = null;
                        continue;
                    }
                }
            }
            
            if ( array_key_exists($param, $inputs) ) {
                
                if ( $this->isNullable() && $inputs[$param] == '' ) {
                    $inputs[$param] = null;
                    continue;
                }

                /**
                 * Validator argument
                 * 
                 * @var string
                 */ 
                $argument = $this->getArguments()->filter(fn(string $argument) => ! in_array($argument, $this->defaultArguments))->first();
                
                $service = new ValidationServiceProvider;
                
                if (FALSE === $service->seemsValidator($argument)) $this->log("$argument is not a valid validator argument.");
                
                /**
                 * Provided options
                 * 
                 * @var array
                 */
                $options = $this->getArgumentOptions($argument);
                
                /**
                 * Validator
                 * 
                 * @var static
                 */
                $validatorClass = ( new ValidationServiceProvider )->getValidator($argument);
                
                $validator = new $validatorClass(parameter: $param);

                /**
                 * Validator options
                 * 
                 * @var array
                 */
                $voptions = $validator->options();
                
                // Check validator options validity.
                foreach ($voptions as $option => $data) {

                    if ( !array_key_exists($option, $options) ) continue;
                    
                    // A required option not provided
                    if ( @ $data['required'] && ! array_key_exists($option, $options) ) $this->log(sprintf("Option %s is required for %s validator.", $option, $argument));
                    
                    // Execute option function
                    if ( $fn = @ $data['function'] ) $options[$option] = $fn($options[$option]);

                    // Set option type
                    if ( @ $data['type'] ) settype($options[$option], $data['type']);

                    // Set array key (for array options)
                    if ( @ $data['keys'] ) {
                        
                        $keys = $data['keys'];
                        $tmp = [];

                        foreach ($keys as $i => $key) {
                            $tmp[$key] = @ $options[$option][$i];
                        }

                        $options[$option] = $tmp;
                    }
                    
                    // Option validator
                    if ( !!@$options[$option] && $fn = @ $data['validator'] AND false === $fn($options[$option]) ) $this->log(sprintf("%s is not a valid option %s value for %s validator.", $options[$option], $option, $argument)); 
                }
                
                foreach ($options as $option => $value) {
                    if ( $option && ! array_key_exists($option, $voptions) ) $this->log(sprintf("%s is not a valid %s validator option.", $option, $argument));
                }

                $value = $inputs[$param];
                $success = $validator->validate($value, $options);
                
                if ( false === $success ) {
                    if ( FALSE === $this->silent ) $this->log(sprintf("Parameter %s is not valid.", $param));

                    return false;
                }

                $inputs[$param] = $value;
                $this->passed($param);
            }
        }

        return true;
    }

    /**
     * Keep track of parameters which validator passed.
     * 
     * @param string $param
     * @return void
     */
    public function passed(string $param) : void
    {
        $this->validated[] = $param;
    }

    private function log(string $message)
    {
        if ($this->silent) return;

        if (Request::current()->hasHeader('X-Inertia')) {
            \Inertia\ComponentData::addError($this->parameter, $message);
        }
        
        throw new ValidationException($message);
    }

    public function __get($name)
    {
        if ($name === 'signature') return $this->signature;
    }

    public function __set($name, $value)
    {
        if ($name === 'signature') $this->signature = $value;
    }
}
