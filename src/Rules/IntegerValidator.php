<?php

namespace Clicalmani\Validation\Rules;

/**
 * Class IntegerValidator
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class IntegerValidator extends NumberValidator
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'integer';
    
    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Returns the array configuration schema for supported rule options.
     *
     * @return array<string, array{
     *     required: bool,
     *     type: string,
     *     default?: mixed,
     *     function?: callable,
     *     validator?: callable
     * }>
     */
    public function options(): array
    {
        // Retrieve parent options
        $options = parent::options();

        $options['format']['validator'] = fn(string $value) => in_array($value, ['integer', 'decimal', 'percentage', 'currency'], true);

        unset($options['round']); 
        unset($options['precision']); 
        
        // Format validation (integer, decimal, percentage, currency)
        $options['format'] = [
            'required' => false,
            'type' => 'string',
            'validator' => fn(string $value) => in_array($value, ['integer', 'decimal', 'percentage', 'currency'], true)
        ];

        // Override parent min and max options to accept integer types
        $options['min']['type'] = 'integer';
        $options['max']['type'] = 'integer';

        $options['allowNegative'] = [
            'required' => false,
            'type' => 'bool',
            'default' => true,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        $options['allowZero'] = [
            'required' => false,
            'type' => 'bool',
            'default' => true,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        return $options;
    }

    /**
     * Validates input numeric value against format, precision, length, and range constraints.
     *
     * @param mixed &$value Target input value to validate and sanitize.
     * @return bool `true` if all enabled validation rules pass, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        $int = $this->sanitizeNumeric($value);

        if ($int === null) {
            $this->addError("The provided value is not a valid integer.");
            return false;
        }

        // allowNegative:false => valeur absolue
        if ($int < 0 && !($this->options['allowNegative'] ?? true)) {
            $this->addError("The number must not be negative."); 
            return false;
        }

        $value = $int;

        if (false === parent::validate($value)) {
            return false;
        }

        $int = $this->cleanValue((int) $value);

        if ($int === null) {
            return false;
        }

        $value = $int;
        return true;
    }

    private function sanitizeNumeric(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (is_finite($value) && floor($value) === $value) ? (int) $value : null;
        }

        if (!is_string($value)) {   // null, arrays, objects, bools
            return null;
        }

        // Thousands separators
        $value = str_replace([' ', ','], '', trim($value));

        if (!preg_match('/^[+-]?\d+$/', $value)) {
            return null;
        }

        // Normalize sign and leading zeros, then reject overflow
        $negative   = $value[0] === '-';
        $digits     = ltrim($value, '+-');
        $digits     = ltrim($digits, '0') ?: '0';
        $normalized = ($negative && $digits !== '0' ? '-' : '') . $digits;

        return (string) (int) $normalized === $normalized ? (int) $normalized : null;
    }

    private function cleanValue(int $value): ?int
    {
        if (!($this->options['allowZero'] ?? true) && $value === 0) {
            $this->addError("The number cannot be equal to zero.");
            return null;
        }

        return $value;
    }

    /**
     * Validates numeric formatting against specified format option.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateFormat(mixed $value): bool
    {
        if (!ctype_digit((string) $value)) {
            $this->addError("The number must be an integer.");
            return false;
        }

        return true;
    }

    /**
     * Formats and returns the primary validation error message.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{min}', '{max}', '{length}'],
                [
                    $this->options['min'] ?? 'N/A',
                    $this->options['max'] ?? 'N/A',
                    $this->options['length'] ?? 'N/A',
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The field '{$this->parameter}' must be a valid integer.";
    }

    /**
     * Gets all recorded validation error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}