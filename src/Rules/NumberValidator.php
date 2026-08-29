<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class NumberValidator
 * 
 * Validates numeric values with support for:
 * - Integers and floating-point numbers
 * - Range constraints (min, max, range)
 * - Specific numerical types (integer, float, positive, negative, natural, even, odd)
 * - Automatic value rounding
 * - Automatic type casting / formatting
 * - Detailed error reporting and message placeholding
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class NumberValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'number';
    
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
     *     function?: callable,
     *     validator?: callable
     * }>
     */
    public function options(): array
    {
        return [
            // Minimum value constraint (supports K, M, G unit suffixes)
            'min' => [
                'required' => false,
                'type' => 'numeric',
                'function' => fn(string $value) => $this->parseNumeric($value)
            ],
            
            // Maximum value constraint (supports K, M, G unit suffixes)
            'max' => [
                'required' => false,
                'type' => 'numeric',
                'function' => fn(string $value) => $this->parseNumeric($value)
            ],
            
            // Range interval constraint (format: min-max)
            'range' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => preg_match('/^[0-9]+(\.[0-9]+)?-[0-9]+(\.[0-9]+)?$/', $value) === 1
            ],
            
            // Numerical subtype rule constraint
            'type' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['integer', 'float', 'positive', 'negative', 'natural', 'even', 'odd'], true)
            ],
            
            // Automatic rounding precision (decimal places)
            'round' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Target output type formatting/casting
            'format' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['int', 'float', 'string'], true)
            ],
            
            // Custom error message override
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input target value against numerical rules and options.
     *
     * @param mixed &$value Reference to the parameter value being validated.
     * @return bool `true` if all validations pass, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Sanitize and parse numeric input
        $value = $this->sanitizeNumber($value);
        
        if ($value === null) {
            $this->addError("The provided value is not a valid number.");
            return false;
        }

        // 2. Validate numeric subtype constraints
        if (!$this->validateType($value)) {
            return false;
        }

        // 3. Validate defined range interval
        if (!$this->validateRange($value)) {
            return false;
        }

        // 4. Validate min/max boundaries
        if (!$this->validateMinMax($value)) {
            return false;
        }

        // 5. Apply optional rounding
        $this->applyRounding($value);

        // 6. Apply optional output formatting
        $this->applyFormat($value);

        return true;
    }

    /**
     * Sanitizes raw value into a valid numeric string or representation.
     *
     * @param mixed $value
     * @return mixed Parsed number string/value or `null` if invalid.
     */
    private function sanitizeNumber(mixed $value): mixed
    {
        // Guard null or empty input
        if ($value === null || $value === '') {
            return null;
        }

        // Return immediately if already numeric
        if (is_numeric($value)) {
            return $value;
        }

        // Parse and clean raw string formats
        if (is_string($value)) {
            $value = trim($value);
            
            // Strip whitespace and thousand separators
            $value = str_replace([' ', ','], ['', '.'], $value);
            
            // Strip invalid characters except digits, dot, plus, minus
            $value = (string) preg_replace('/[^0-9.\-+]/', '', $value);
            
            if (is_numeric($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Parses numeric notation strings including shorthand units (K, M, G).
     *
     * @param string $value
     * @return float|int
     */
    private function parseNumeric(string $value): float|int
    {
        $value = trim($value);
        
        if (preg_match('/^(\d+\.?\d*)([KMG])?$/i', $value, $matches)) {
            $number = (float) $matches[1];
            $unit = strtoupper($matches[2] ?? '');
            
            return match ($unit) {
                'K' => $number * 1000,
                'M' => $number * 1000000,
                'G' => $number * 1000000000,
                default => $number,
            };
        }
        
        return (float) $value;
    }

    /**
     * Validates the specified numeric subtype constraint.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateType(mixed $value): bool
    {
        $type = $this->options['type'] ?? null;

        if (!$type) {
            return true;
        }

        switch ($type) {
            case 'integer':
                if (!is_int($value) && !ctype_digit((string) $value)) {
                    $this->addError("The number must be an integer.");
                    return false;
                }
                break;

            case 'float':
                if (!is_float($value) && !preg_match('/^\d+\.\d+$/', (string) $value)) {
                    $this->addError("The number must be a decimal/float.");
                    return false;
                }
                break;

            case 'positive':
                if ($value <= 0) {
                    $this->addError("The number must be positive.");
                    return false;
                }
                break;

            case 'negative':
                if ($value >= 0) {
                    $this->addError("The number must be negative.");
                    return false;
                }
                break;

            case 'natural':
                if ($value < 0 || (!is_int($value) && !ctype_digit((string) $value))) {
                    $this->addError("The number must be a natural integer (0, 1, 2, ...).");
                    return false;
                }
                break;

            case 'even':
                if ((!is_int($value) && !ctype_digit((string) $value)) || ((int) $value % 2 !== 0)) {
                    $this->addError("The number must be an even integer.");
                    return false;
                }
                break;

            case 'odd':
                if ((!is_int($value) && !ctype_digit((string) $value)) || ((int) $value % 2 === 0)) {
                    $this->addError("The number must be an odd integer.");
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Validates input number against specified interval range string.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateRange(mixed $value): bool
    {
        $range = $this->options['range'] ?? null;

        if (!$range) {
            return true;
        }

        [$min, $max] = explode('-', $range);
        $min = (float) $min;
        $max = (float) $max;

        if ($value < $min || $value > $max) {
            $this->addError("The number must be between {$min} and {$max}.");
            return false;
        }

        return true;
    }

    /**
     * Validates minimum and maximum value boundary limits.
     *
     * @param mixed &$value
     * @return bool
     */
    private function validateMinMax(mixed &$value): bool
    {
        $min = $this->options['min'] ?? null;
        $max = $this->options['max'] ?? null;

        // Check minimum constraint
        if ($min !== null) {
            if ($value < $min) {
                if ($this->options['format'] ?? null) {
                    $value = $min;
                } else {
                    $this->addError("The number must not be less than {$min}.");
                    return false;
                }
            }
        }

        // Check maximum constraint
        if ($max !== null) {
            if ($value > $max) {
                if ($this->options['format'] ?? null) {
                    $value = $max;
                } else {
                    $this->addError("The number must not be greater than {$max}.");
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Rounds numeric value when 'round' precision option is configured.
     *
     * @param mixed &$value
     * @return void
     */
    private function applyRounding(mixed &$value): void
    {
        $round = $this->options['round'] ?? null;

        if ($round === null || !is_numeric($value)) {
            return;
        }

        $value = round((float) $value, $round);
    }

    /**
     * Applies data type casting based on configured format option.
     *
     * @param mixed &$value
     * @return void
     */
    private function applyFormat(mixed &$value): void
    {
        $format = $this->options['format'] ?? null;

        if (!$format || !is_numeric($value)) {
            return;
        }

        switch ($format) {
            case 'int':
                $value = (int) $value;
                break;
            case 'float':
                $value = (float) $value;
                break;
            case 'string':
                $value = (string) $value;
                break;
        }
    }

    /**
     * Appends an error message to internal tracking array and logs it.
     *
     * @param string $message
     * @return void
     */
    private function addError(string $message): void
    {
        $this->errors[] = $message;
        $this->log($message);
    }

    /**
     * Resolves and returns the validation error message with populated placeholders.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{min}', '{max}', '{range}'],
                [
                    $this->options['min'] ?? 'N/A',
                    $this->options['max'] ?? 'N/A',
                    $this->options['range'] ?? 'N/A'
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The field '{$this->parameter}' must be a valid number.";
    }

    /**
     * Returns all recorded error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}