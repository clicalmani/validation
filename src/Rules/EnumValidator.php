<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class EnumValidator
 * 
 * Validates that an input value exists within a predefined list of allowed values.
 * Features include:
 * - Allowed values parsing (scalar, list arrays, JSON strings)
 * - Case-sensitive or case-insensitive comparison options
 * - Value sanitization (trimming, case modification)
 * - Strict type-checking or loose comparison modes
 * - Detailed error feedback and logging
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class EnumValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'enum';
    
    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * The sanitized value after successful validation.
     *
     * @var mixed
     */
    protected mixed $validatedValue = null;

    /**
     * Returns the configuration option schema for the validator rule.
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
            // List of allowed values (array or comma-separated string)
            'list' => [
                'required' => true,
                'type' => 'array',
                'function' => fn(string $value) => $this->parseList($value)
            ],
            
            // Enable case-insensitive match for string evaluations
            'caseInsensitive' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Pre-validation value sanitization option
            'sanitize' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['trim', 'lower', 'upper'], true)
            ],
            
            // Strict comparison mode (value and data type matching)
            'strict' => [
                'required' => false,
                'type' => 'bool',
                'default' => true,
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Custom error message pattern
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input value membership against defined list.
     *
     * @param mixed &$value Reference to input value being validated.
     * @return bool `true` if valid, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Sanitize the provided input value
        $value = $this->sanitizeValue($value);
        
        if ($value === null) {
            $this->addError("The provided value is invalid.");
            return false;
        }

        // 2. Prepare allowed values list according to settings
        $list = $this->prepareList();

        // 3. Perform membership verification
        $isValid = $this->isInList($value, $list);

        if (!$isValid) {
            $this->addError($this->getErrorMessage($value, $list));
            return false;
        }

        $this->validatedValue = $value;

        return true;
    }

    /**
     * Parses input list string into an enumerated array of typed values.
     *
     * @param string $value
     * @return array<int, mixed>
     */
    private function parseList(string $value): array
    {
        // Parse raw JSON input if formatted as an array or object representation
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Split comma-delimited strings
        $items = array_map('trim', explode(',', $value));

        // Convert string values into native scalars where applicable
        foreach ($items as &$item) {
            if (is_numeric($item)) {
                if (str_contains($item, '.')) {
                    $item = (float) $item;
                } else {
                    $item = (int) $item;
                }
            } elseif ($item === 'true') {
                $item = true;
            } elseif ($item === 'false') {
                $item = false;
            } elseif ($item === 'null') {
                $item = null;
            }
        }

        return $items;
    }

    /**
     * Sanitizes input value according to configured option.
     *
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value) || is_numeric($value)) {
            return $value;
        }

        if (is_string($value)) {
            $sanitize = $this->options['sanitize'] ?? null;

            switch ($sanitize) {
                case 'trim':
                    $value = trim($value);
                    break;
                case 'lower':
                    $value = strtolower($value);
                    break;
                case 'upper':
                    $value = strtoupper($value);
                    break;
            }

            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Prepares allowed list values according to case-sensitivity settings.
     *
     * @return array<int, mixed>
     */
    private function prepareList(): array
    {
        $list = $this->options['list'];
        $caseInsensitive = $this->options['caseInsensitive'] ?? false;

        if ($caseInsensitive) {
            $prepared = [];
            foreach ($list as $item) {
                if (is_string($item)) {
                    $prepared[] = strtolower($item);
                } else {
                    $prepared[] = $item;
                }
            }
            return $prepared;
        }

        return $list;
    }

    /**
     * Evaluates if a given value matches any element in the list.
     *
     * @param mixed $value
     * @param array<int, mixed> $list
     * @return bool
     */
    private function isInList(mixed $value, array $list): bool
    {
        $strict = $this->options['strict'] ?? true;
        $caseInsensitive = $this->options['caseInsensitive'] ?? false;

        // Convert target value for case-insensitive evaluation
        if ($caseInsensitive && is_string($value)) {
            $value = strtolower($value);
        }

        // Compare against configured enumeration entries
        foreach ($list as $item) {
            if ($strict) {
                if ($value === $item) {
                    return true;
                }
            } else {
                if ($value == $item) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generates error message for failed value match.
     *
     * @param mixed $value
     * @param array<int, mixed> $list
     * @return string
     */
    private function getErrorMessage(mixed $value, array $list): string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}', '{list}'],
                [is_scalar($value) ? (string) $value : json_encode($value), implode(', ', $list)],
                $customMessage
            );
        }

        $valueStr = is_scalar($value) ? (string) $value : json_encode($value);
        return "The value '{$valueStr}' is not allowed. Allowed values are: " . implode(', ', $list);
    }

    /**
     * Appends error message to stack and logs entry.
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
     * Retrieves primary user message for output presentation.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}', '{list}'],
                [$this->parameter, implode(', ', $this->options['list'])],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The value for field '{$this->parameter}' is not allowed.";
    }

    /**
     * Returns all error messages captured by execution.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns the sanitized value produced during validation process.
     *
     * @return mixed
     */
    public function getValidatedValue(): mixed
    {
        return $this->validatedValue;
    }

    /**
     * Independent helper to test membership of a value in a list.
     *
     * @param mixed $value
     * @param array<int, mixed> $list
     * @return bool
     */
    public function inList(mixed $value, array $list): bool
    {
        $this->options['list'] = $list;
        return $this->isInList($value, $this->prepareList());
    }
}