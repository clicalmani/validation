<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class StringsValidator
 * 
 * Validates array inputs of strings or delimited string values.
 * Features include:
 * - Parsing arrays, JSON strings, or delimited single inputs
 * - Per-string validation (length, regex format patterns)
 * - Sanitization capabilities (trim, case manipulation, tag stripping)
 * - Deduplication and item count constraints (min/max bounds)
 * - Optional joining of output elements into a formatted string
 * - Detailed error handling tracking valid vs invalid entries
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class StringsValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'string[]';

    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Set of validated string elements.
     *
     * @var array<int, string>
     */
    protected array $validStrings = [];

    /**
     * Collection of metadata for elements failing validation constraints.
     *
     * @var array<int, array{index: int, value: mixed, error: string}>
     */
    protected array $invalidStrings = [];

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
            // Exact character length required per string
            'length' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum character length requirement per string
            'min' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Maximum character length threshold per string
            'max' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Predefined format patterns (email, url, alpha, alnum, numeric, slug)
            'format' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array(
                    $value,
                    ['email', 'url', 'alpha', 'alnum', 'numeric', 'slug'],
                    true
                )
            ],
            
            // Per-string sanitization (trim, lower, upper, ucfirst, strip_tags)
            'sanitize' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array(
                    $value,
                    ['trim', 'lower', 'upper', 'ucfirst', 'strip_tags'],
                    true
                )
            ],
            
            // Minimum required array elements count
            'minItems' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Maximum allowed array elements count
            'maxItems' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Remove duplicate array entries
            'distinct' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Allow empty string items in dataset
            'allowEmpty' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Join valid string elements into single string output
            'join' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Glue string used when joining output array
            'separator' => [
                'required' => false,
                'type' => 'string',
                'default' => ','
            ],
            
            // Custom error message pattern
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input array of strings according to defined constraints.
     *
     * @param mixed &$value Reference to the input data being validated.
     * @return bool `true` if valid, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Input normalization
        $items = $this->normalizeInput($value);
        
        if ($items === null) {
            $this->addError("The provided string array is invalid.");
            return false;
        }

        // 2. Filter empty string values if disallowed
        $allowEmpty = $this->options['allowEmpty'] ?? false;
        if (!$allowEmpty) {
            $items = array_values(array_filter($items, function ($item) {
                return $item !== '' && $item !== null;
            }));
        }

        // 3. Verify total element counts
        if (!$this->validateItemCount($items)) {
            return false;
        }

        // 4. Remove duplicate elements if distinct option set
        $distinct = $this->options['distinct'] ?? false;
        if ($distinct) {
            $items = array_values(array_unique($items));
        }

        // 5. Per-element validation pass
        $this->validStrings = [];
        $this->invalidStrings = [];
        $this->errors = [];

        foreach ($items as $index => $item) {
            if ($this->validateSingleString($item, $index)) {
                $this->validStrings[] = $item;
            } else {
                $lastError = $this->getLastError();
                $this->invalidStrings[] = [
                    'index' => $index,
                    'value' => $item,
                    'error' => $lastError
                ];
            }
        }

        // 6. Output validation checks
        if (!empty($this->invalidStrings)) {
            if (count($this->invalidStrings) === 1) {
                $this->addError($this->errors[0]);
            } else {
                $total = count($items);
                $invalidCount = count($this->invalidStrings);
                $this->addError("{$invalidCount} invalid item(s) found out of {$total}.");
            }

            return false;
        }

        // 7. Format output payload
        $join = $this->options['join'] ?? false;
        if ($join) {
            $separator = $this->options['separator'] ?? ',';
            $value = implode($separator, $this->validStrings);
        } else {
            $value = $this->validStrings;
        }

        return true;
    }

    /**
     * Normalizes flexible input payloads into indexed string arrays.
     *
     * @param mixed $value
     * @return array<int, mixed>|null
     */
    private function normalizeInput(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            // Handle URL-encoded payload strings
            if (str_contains($value, '%')) {
                $value = urldecode($value);
            }

            // Decode JSON strings into native arrays
            if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            // Parse separated string listings
            $separator = $this->options['separator'] ?? ',';
            return array_map('trim', explode($separator, $value));
        }

        if (is_numeric($value)) {
            return [(string) $value];
        }

        return null;
    }

    /**
     * Validates item count bounds for dataset.
     *
     * @param array<int, mixed> $items
     * @return bool
     */
    private function validateItemCount(array $items): bool
    {
        $count = count($items);

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
     * Validates a single element against length and format specifications.
     *
     * @param mixed &$item Reference to element within iteration loop.
     * @param int $index Array index position of target element.
     * @return bool
     */
    private function validateSingleString(mixed &$item, int $index): bool
    {
        // 1. Scalar cast evaluation
        if (!is_string($item) && !is_numeric($item)) {
            $this->addError("Element at index {$index} is not a valid string.");
            return false;
        }

        $value = (string) $item;

        // 2. Perform element sanitization
        $this->applySanitize($value);

        // 3. Length checks
        if (!$this->validateLength($value, $index)) {
            return false;
        }

        // 4. Format checks
        if (!$this->validateFormat($value, $index)) {
            return false;
        }

        $item = $value;

        return true;
    }

    /**
     * Applies configured string modification algorithms.
     *
     * @param string &$value Target string passed by reference.
     * @return void
     */
    private function applySanitize(string &$value): void
    {
        $sanitize = $this->options['sanitize'] ?? null;

        if (!$sanitize) {
            return;
        }

        switch ($sanitize) {
            case 'trim':
                $value = trim($value);
                break;
            case 'lower':
                $value = mb_strtolower($value, 'UTF-8');
                break;
            case 'upper':
                $value = mb_strtoupper($value, 'UTF-8');
                break;
            case 'ucfirst':
                $lower = mb_strtolower($value, 'UTF-8');
                $firstChar = mb_substr($lower, 0, 1, 'UTF-8');
                $remainder = mb_substr($lower, 1, null, 'UTF-8');
                $value = mb_strtoupper($firstChar, 'UTF-8') . $remainder;
                break;
            case 'strip_tags':
                $value = strip_tags($value);
                break;
        }
    }

    /**
     * Validates character lengths using multibyte string calculations.
     *
     * @param string $value
     * @param int $index
     * @return bool
     */
    private function validateLength(string $value, int $index): bool
    {
        $length = mb_strlen($value, 'UTF-8');

        // Exact character length verification
        if ($exactLength = $this->options['length'] ?? null) {
            if ($length !== $exactLength) {
                $this->addError(
                    "Element at index {$index} ('{$value}') must contain exactly {$exactLength} characters."
                );
                return false;
            }
            return true;
        }

        // Minimum character length verification
        if ($min = $this->options['min'] ?? null) {
            if ($length < $min) {
                $this->addError(
                    "Element at index {$index} ('{$value}') must contain at least {$min} characters."
                );
                return false;
            }
        }

        // Maximum character length verification
        if ($max = $this->options['max'] ?? null) {
            if ($length > $max) {
                $this->addError(
                    "Element at index {$index} ('{$value}') must not exceed {$max} characters."
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validates string format rules via regex patterns or filter functions.
     *
     * @param string $value
     * @param int $index
     * @return bool
     */
    private function validateFormat(string $value, int $index): bool
    {
        $format = $this->options['format'] ?? null;

        if (!$format) {
            return true;
        }

        $valid = match ($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'alpha' => preg_match('/^\p{L}+$/u', $value) === 1,
            'alnum' => preg_match('/^[\p{L}\p{N}]+$/u', $value) === 1,
            'numeric' => is_numeric($value),
            'slug' => preg_match('/^[a-z0-9-]+$/', $value) === 1,
            default => true
        };

        if (!$valid) {
            $this->addError(
                "Element at index {$index} ('{$value}') does not match the '{$format}' format."
            );
            return false;
        }

        return true;
    }

    /**
     * Retrieves the most recent error appended to tracking stack.
     *
     * @return string
     */
    private function getLastError(): string
    {
        if (empty($this->errors)) {
            return "Unknown error occurred.";
        }

        return end($this->errors);
    }

    /**
     * Appends an error message to the local stack and log destination.
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

        return count($this->errors) . " invalid item(s): " . implode('; ', $this->errors);
    }

    /**
     * Returns all error messages captured during execution.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns list of items successfully passing validation.
     *
     * @return array<int, string>
     */
    public function getValidStrings(): array
    {
        return $this->validStrings;
    }

    /**
     * Returns metadata for elements failing validation constraints.
     *
     * @return array<int, array{index: int, value: mixed, error: string}>
     */
    public function getInvalidStrings(): array
    {
        return $this->invalidStrings;
    }
}