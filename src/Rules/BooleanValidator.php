<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class BooleanValidator
 *
 * Validates boolean values with support for:
 * - Native booleans (true, false)
 * - String representations (true/false, 1/0, yes/no, on/off)
 * - Numeric representations (1, 0)
 * - Automatic type casting & value normalization
 * - Strict type enforcement mode
 * - Detailed error handling and customizable messages
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class BooleanValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'boolean';

    /**
     * Internal array containing error messages captured during validation.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Configuration option definitions for boolean validation rules.
     *
     * @return array<string, array{
     *     required: bool,
     *     type: string,
     *     default?: mixed,
     *     function?: callable
     * }>
     */
    public function options(): array
    {
        return [
            // Default value applied when target value is null or empty
            'default' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ],

            // Enable loose string representation parsing
            'strings' => [
                'required' => false,
                'type' => 'bool',
                'default' => true,
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ],

            // Enable strict type evaluation (accepts only native boolean values)
            'strict' => [
                'required' => false,
                'type' => 'bool',
                'default' => false,
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ],

            // Custom error message override
            'message' => [
                'required' => false,
                'type' => 'string',
            ],
        ];
    }

    /**
     * Validates and casts an input value to a native boolean representation.
     *
     * @param mixed &$value Target data reference to validate and cast.
     * @return bool `true` on success, `false` on failure.
     */
    public function validate(mixed &$value): bool
    {
        $this->errors = [];

        // 1. Handle null or empty string input values
        if ($value === null || $value === '') {
            if (array_key_exists('default', $this->options)) {
                $value = (bool) $this->options['default'];
                return true;
            }

            // Check if parameter presence is strictly required
            if ($this->isRequired()) {
                $this->addError("The boolean value is required.");
                return false;
            }

            return true;
        }

        // 2. Parse boolean representation
        $result = $this->parseBoolean($value);

        if ($result === null) {
            $this->addError($this->getErrorMessage($value));
            return false;
        }

        // 3. Update reference value with cast boolean result
        $value = $result;

        return true;
    }

    /**
     * Parses a raw value into a native boolean representation.
     *
     * @param mixed $value
     * @return bool|null Returns parsed boolean or `null` if invalid.
     */
    private function parseBoolean(mixed $value): ?bool
    {
        // Already a native boolean
        if (is_bool($value)) {
            return $value;
        }

        // Reject non-booleans if strict mode is enabled
        if ($this->options['strict'] ?? false) {
            return null;
        }

        // Numeric values (0 = false, non-zero = true)
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        // String representations
        if (is_string($value)) {
            $value = strtolower(trim($value));

            // Truthy equivalents
            if (in_array($value, ['true', '1', 'yes', 'on', 'y', 't', 'ok', 'oui'], true)) {
                return true;
            }

            // Falsy equivalents
            if (in_array($value, ['false', '0', 'no', 'off', 'n', 'f', 'non', ''], true)) {
                return false;
            }
        }

        return null;
    }

    /**
     * Checks if the target parameter is required.
     *
     * @return bool
     */
    private function isRequired(): bool
    {
        return $this->hasArgument('required');
    }

    /**
     * Generates a descriptive error message for an invalid input value.
     *
     * @param mixed $value
     * @return string
     */
    private function getErrorMessage(mixed $value): string
    {
        $customMessage = $this->options['message'] ?? null;
        $valueStr = is_scalar($value) ? (string) $value : gettype($value);

        if ($customMessage) {
            return str_replace(
                ['{value}'],
                [$valueStr],
                $customMessage
            );
        }

        return "The value '{$valueStr}' is not a valid boolean. Allowed values: true, false, 1, 0, yes, no, on, off.";
    }

    /**
     * Records a validation error and logs it.
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
     * Generates a human-readable primary error message, substituting parameter placeholders.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}'],
                [(string) $this->parameter],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The field '{$this->parameter}' must be a valid boolean (true/false, 1/0, yes/no).";
    }

    /**
     * Retrieves all recorded validation error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Normalizes a given value into a native boolean representation.
     *
     * @param mixed $value
     * @return bool|null
     */
    public function normalize(mixed $value): ?bool
    {
        return $this->parseBoolean($value);
    }

    /**
     * Checks whether a given value is a valid boolean representation.
     *
     * @param mixed $value
     * @return bool
     */
    public function isValidBoolean(mixed $value): bool
    {
        return $this->parseBoolean($value) !== null;
    }
}