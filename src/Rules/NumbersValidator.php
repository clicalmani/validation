<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class NumbersValidator
 * 
 * Validates an array or delimited string of numbers with support for:
 * - Arrays or comma-separated numeric strings (and JSON arrays)
 * - Individual element validation
 * - Range and boundary options (min, max, range)
 * - Array size limits (minItems, maxItems)
 * - Deduplication (distinct)
 * - String joining on output
 * - Detailed error tracking and reporting
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class NumbersValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'number[]';
    
    /**
     * Recorded error messages encountered during validation.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * List of successfully validated number items.
     *
     * @var array<int, mixed>
     */
    protected array $validNumbers = [];

    /**
     * Details about elements that failed validation.
     *
     * @var array<int, array{index: int|string, value: mixed, error: string}>
     */
    protected array $invalidNumbers = [];

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
        return [
            // Minimum value constraint for each item
            'min' => [
                'required' => false,
                'type' => 'numeric',
                'function' => fn(string $value) => (float) $value
            ],
            
            // Maximum value constraint for each item
            'max' => [
                'required' => false,
                'type' => 'numeric',
                'function' => fn(string $value) => (float) $value
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
            
            // Minimum required elements count
            'minItems' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Maximum allowed elements count
            'maxItems' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Remove duplicate entries
            'distinct' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Allow empty items inside collection
            'allowEmpty' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Join elements into string output
            'join' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Delimiter separator used when splitting/joining strings
            'separator' => [
                'required' => false,
                'type' => 'string',
                'default' => ','
            ],
            
            // Custom error message override
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input sequence against numerical array options.
     *
     * @param mixed &$value Reference to input value being validated.
     * @return bool `true` if all items pass validation, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Normalize input to array format
        $value = $this->normalizeInput($value);
        
        if ($value === null) {
            $this->addError("The provided array of numbers is invalid.");
            return false;
        }

        // 2. Filter empty items if disallowed
        if (!($this->options['allowEmpty'] ?? false)) {
            $value = array_filter($value, fn($item) => $item !== '' && $item !== null);
        }

        // 3. Validate element count constraints
        if (!$this->validateItemCount($value)) {
            return false;
        }

        // 4. Deduplicate items if configured
        if ($this->options['distinct'] ?? false) {
            $value = array_values(array_unique($value));
        }

        // 5. Reset execution states and process each number individually
        $this->validNumbers = [];
        $this->invalidNumbers = [];
        $this->errors = [];

        foreach ($value as $index => $item) {
            if ($this->validateSingleNumber($item)) {
                $this->validNumbers[] = $item;
            } else {
                $this->invalidNumbers[] = [
                    'index' => $index,
                    'value' => $item,
                    'error' => $this->getLastError()
                ];
                $this->errors[] = "The item at index {$index} ({$item}) is invalid.";
            }
        }

        // 6. Verify processing results
        if (!empty($this->errors)) {
            if (count($this->errors) === 1) {
                $this->addError($this->errors[0]);
            } else {
                $this->addError(count($this->errors) . " invalid element(s) out of " . count($value) . ".");
            }
            return false;
        }

        // 7. Format output payload
        if ($this->options['join'] ?? false) {
            $value = implode($this->options['separator'] ?? ',', $this->validNumbers);
        } else {
            $value = $this->validNumbers;
        }

        return true;
    }

    /**
     * Normalizes input into an array representation (supporting raw array, CSV strings, JSON).
     *
     * @param mixed $value
     * @return array<int|string, mixed>|null
     */
    private function normalizeInput(mixed $value): ?array
    {
        // Direct array payload
        if (is_array($value)) {
            return $value;
        }

        // String representation handling
        if (is_string($value)) {
            if (str_contains($value, '%')) {
                $value = urldecode($value);
            }

            // JSON array parsing
            if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            // Delimited string split
            $separator = $this->options['separator'] ?? ',';
            return array_map('trim', explode($separator, $value));
        }

        // Single numeric value wrapper
        if (is_numeric($value)) {
            return [$value];
        }

        return null;
    }

    /**
     * Validates minimum and maximum count of elements in the collection.
     *
     * @param array $value
     * @return bool
     */
    private function validateItemCount(array $value): bool
    {
        $count = count($value);

        if ($min = $this->options['minItems'] ?? null) {
            if ($count < $min) {
                $this->addError("The array must contain at least {$min} item(s), {$count} provided.");
                return false;
            }
        }

        if ($max = $this->options['maxItems'] ?? null) {
            if ($count > $max) {
                $this->addError("The array must contain at most {$max} item(s), {$count} provided.");
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a single individual number against defined scalar rules.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateSingleNumber(mixed $value): bool
    {
        // Verify numeric base type
        if (!is_numeric($value)) {
            $this->addError("The value '{$value}' is not a valid number.");
            return false;
        }

        // Subtype constraint validation
        if (!$this->validateNumberType($value)) {
            return false;
        }

        // Boundary limits validation
        if (!$this->validateNumberConstraints($value)) {
            return false;
        }

        // Interval range validation
        if (!$this->validateNumberRange($value)) {
            return false;
        }

        return true;
    }

    /**
     * Validates numeric subtype options for an individual item.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateNumberType(mixed $value): bool
    {
        $type = $this->options['type'] ?? null;

        if (!$type) {
            return true;
        }

        switch ($type) {
            case 'integer':
                if (!is_int($value) && !ctype_digit((string) $value)) {
                    $this->addError("The value '{$value}' must be an integer.");
                    return false;
                }
                break;

            case 'float':
                if (!is_float($value) && !preg_match('/^\d+\.\d+$/', (string) $value)) {
                    $this->addError("The value '{$value}' must be a decimal.");
                    return false;
                }
                break;

            case 'positive':
                if ($value <= 0) {
                    $this->addError("The value '{$value}' must be positive.");
                    return false;
                }
                break;

            case 'negative':
                if ($value >= 0) {
                    $this->addError("The value '{$value}' must be negative.");
                    return false;
                }
                break;

            case 'natural':
                if ($value < 0 || (!is_int($value) && !ctype_digit((string) $value))) {
                    $this->addError("The value '{$value}' must be a natural integer.");
                    return false;
                }
                break;

            case 'even':
                if ((!is_int($value) && !ctype_digit((string) $value)) || ((int) $value % 2 !== 0)) {
                    $this->addError("The value '{$value}' must be an even integer.");
                    return false;
                }
                break;

            case 'odd':
                if ((!is_int($value) && !ctype_digit((string) $value)) || ((int) $value % 2 === 0)) {
                    $this->addError("The value '{$value}' must be an odd integer.");
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Validates individual item min/max boundaries.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateNumberConstraints(mixed $value): bool
    {
        $min = $this->options['min'] ?? null;
        $max = $this->options['max'] ?? null;

        if ($min !== null && $value < $min) {
            $this->addError("The value '{$value}' must not be less than {$min}.");
            return false;
        }

        if ($max !== null && $value > $max) {
            $this->addError("The value '{$value}' must not be greater than {$max}.");
            return false;
        }

        return true;
    }

    /**
     * Validates individual item range interval.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateNumberRange(mixed $value): bool
    {
        $range = $this->options['range'] ?? null;

        if (!$range) {
            return true;
        }

        [$min, $max] = explode('-', $range);
        $min = (float) $min;
        $max = (float) $max;

        if ($value < $min || $value > $max) {
            $this->addError("The value '{$value}' must be between {$min} and {$max}.");
            return false;
        }

        return true;
    }

    /**
     * Retrieves the most recently added error message.
     *
     * @return string
     */
    private function getLastError(): string
    {
        $errors = $this->getErrors();
        return end($errors) ?: "Unknown error";
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
     * Resolves and returns formatted validation error message.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{count}', '{errors}'],
                [(string) count($this->errors), implode('; ', $this->errors)],
                $customMessage
            );
        }

        if (empty($this->errors)) {
            return null;
        }

        if (count($this->errors) === 1) {
            return $this->errors[0];
        }

        return count($this->errors) . " invalid element(s): " . implode('; ', $this->errors);
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

    /**
     * Returns the array of valid numbers recorded during validation.
     *
     * @return array<int, mixed>
     */
    public function getValidNumbers(): array
    {
        return $this->validNumbers;
    }

    /**
     * Returns the array of failed items recorded during validation.
     *
     * @return array<int, array{index: int|string, value: mixed, error: string}>
     */
    public function getInvalidNumbers(): array
    {
        return $this->invalidNumbers;
    }
}