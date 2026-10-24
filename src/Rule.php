<?php
namespace Clicalmani\Validation;

use Clicalmani\Core\Http\Request;
use Clicalmani\Core\Support\Facades\Log;
use Clicalmani\Validation\Exceptions\ValidationException;

/**
 * Rule class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani
 */
class Rule extends InputParser implements RuleInterface
{
    protected array $options = [];

    /** @var string[] */
    protected array $errors = [];

    protected static string $argument = '';

    const DEFAULT_ARGUMENTS = ['required', 'nullable', 'sometimes', 'hash', 'confirmed'];

    public function __construct(protected ?string $parameter = null, protected string $pattern = '')
    {
        //
    }

    public static function getArgument() : string { return static::$argument; }
    public static function getDefaultArguments() : array { return static::DEFAULT_ARGUMENTS; }

    public function options(): array { return []; }

    /** Options communes à toutes les règles. */
    protected function commonOptions(): array
    {
        return ['message' => ['required' => false, 'type' => 'string']];
    }

    /** Placeholders supplémentaires ({min}, {formats}…) pour les messages. */
    protected function placeholders(): array { return []; }

    /** Accès à une option résolue (après checkOptions). */
    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function validate(mixed &$value): bool
    {
        throw new ValidationException(sprintf("%s must override the validate method", $this::class));
    }

    public function isRequired() : bool   { return $this->hasArgument('required'); }
    public function isNullable() : bool   { return $this->hasArgument('nullable'); }
    public function isSometimes() : bool  { return $this->hasArgument('sometimes'); }
    public function isHash() : bool       { return $this->hasArgument('hash'); }
    public function isConfirmed() : bool  { return $this->hasArgument('confirmed'); }

    protected function hasArgument(string $argument) : bool
    {
        return Validator::getArguments($this->pattern)->contains($argument);
    }

    /**
     * @throws ValidationException
     */
    public function checkOptions() : bool
    {
        $this->options = Validator::getArgumentOptions($this->pattern);
        $definitions   = $this->options() + $this->commonOptions();
        $rule          = static::$argument;

        foreach (array_keys($this->options) as $key) {
            if (!array_key_exists($key, $definitions)) {
                throw new ValidationException(sprintf(
                    "%s is not a valid %s rule option; expected [%s] options, got %s",
                    $key, $rule, join(',', array_keys($definitions)), $key
                ));
            }
        }

        foreach ($definitions as $key => $definition) {
            $provided = array_key_exists($key, $this->options);

            if (!$provided && !array_key_exists('default', $definition) && empty($definition['required'])) {
                continue;
            }

            $value = $this->options[$key] ?? null;

            // Drapeau booléen fourni sans valeur ("strict") => "strict:true".
            // Uniquement si l'option est présente : absente, on veut le défaut.
            if ($provided && $value === null && ($definition['type'] ?? null) === 'bool') {
                $value = 'true';
            }

            (new RuleOption(
                $key,
                $value,
                $definition['required'] ?? null,
                $definition['type'] ?? null,
                $definition['keys'] ?? [],
                $definition['function'] ?? $definition['func'] ?? null,
                $definition['validator'] ?? null,
                $definition['default'] ?? null,
                $rule
            ))->validate();

            $this->options[$key] = $value;
        }

        return true;
    }

    /**
     * Enregistre une erreur de validation de la valeur.
     * L'option `message` prime ; {param}, {value} et placeholders() sont substitués.
     */
    protected function addError(string $message, mixed $value = null): void
    {
        $message = $this->options['message'] ?? $message;

        $message = strtr($message, [
            '{param}' => (string) $this->parameter,
            '{value}' => is_scalar($value) ? (string) $value : gettype($value),
        ] + $this->placeholders());

        $this->errors[] = $message;
        $this->log($message);
    }

    public function getErrors(): array { return $this->errors; }

    public function message() : ?string { return $this->errors[0] ?? null; }

    /** Échappe un identifiant SQL (nom de colonne) interpolé dans une requête. */
    protected function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function log(?string $message = null) : void
    {
        $errors = json_decode(session()->get('errors', '{}'), true);
        
        if ($request = Request::current() AND $errorBag = $request->getHeaderLine('X-Inertia-Error-Bag')) {
            $errors[$errorBag][$this->parameter] = $message;
        } else $errors[$this->parameter] = $message;
        
        session()->set('errors', json_encode($errors));
        
        if (Request::current()?->hasHeader('X-Inertia')) {
            return;
        }

        switch (Validator::getErrorLevel()) {
            case Validator::ERROR_SILENCE: break;
            case Validator::ERROR_WARNING:
                Log::warning($message, self::class, __LINE__);
                break;
            case Validator::ERROR_THROW: throw new ValidationException($message);
        }
    }
}