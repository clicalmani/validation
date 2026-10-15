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

        if ($value === null || $value === '') {
            if (array_key_exists('default', $this->options)) {
                $value = (bool) $this->options['default'];
            }
            return true;
        }

        $result = $this->parse($value);

        if ($result === null) {
            $this->addError("The value '{value}' is not a valid boolean. Allowed values: true, false, 1, 0, yes, no, on, off.", $value);
            return false;
        }

        $value = $result;
        return true;
    }

    /**
     * Parses a raw value into a native boolean representation.
     *
     * @param mixed $value
     * @return bool|null Returns parsed boolean or `null` if invalid.
     */
    private function parse(mixed $value): ?bool
    {
        if (is_bool($value)) return $value;
        if ($this->options['strict'] ?? false) return null;

        // Entiers : uniquement 0 / 1 (42 n'est pas un booléen de formulaire)
        if (is_int($value)) return match ($value) { 1 => true, 0 => false, default => null };

        if (!is_string($value) || !($this->options['strings'] ?? true)) return null;

        $value = strtolower(trim($value));

        if (in_array($value, ['true', '1', 'yes', 'on', 'y', 't', 'ok', 'oui'], true)) return true;
        if (in_array($value, ['false', '0', 'no', 'off', 'n', 'f', 'non'], true))     return false;

        return null;
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
        return $this->parse($value);
    }

    /**
     * Checks whether a given value is a valid boolean representation.
     *
     * @param mixed $value
     * @return bool
     */
    public function isValidBoolean(mixed $value): bool
    {
        return $this->parse($value) !== null;
    }
}