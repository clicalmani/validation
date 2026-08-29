<?php

namespace Clicalmani\Validation\Rules;

/**
 * Class NumericValidator
 * 
 * Validates numeric values with support for:
 * - Integers and floating-point numbers
 * - Range constraints (min, max)
 * - Length constraints (digit count)
 * - Precision constraints (decimal places)
 * - Predefined formats (integer, decimal, percentage, currency)
 * - Automatic rounding and sign sanitization
 * - Detailed error reporting and message replacement
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class NumericValidator extends NumberValidator
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'numeric';
    
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
        
        // Max digit count length constraint
        $options['length'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        // Decimal precision constraint
        $options['precision'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        // Format validation (integer, decimal, percentage, currency)
        $options['format'] = [
            'required' => false,
            'type' => 'string',
            'validator' => fn(string $value) => in_array($value, ['integer', 'decimal', 'percentage', 'currency'], true)
        ];

        // Decimal rounding precision level
        $options['round'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        // Allow negative numbers (true/false)
        $options['allowNegative'] = [
            'required' => false,
            'type' => 'bool',
            'default' => true,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        // Allow zero values (true/false)
        $options['allowZero'] = [
            'required' => false,
            'type' => 'bool',
            'default' => true,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        // Override parent min and max options to accept numeric types
        $options['min']['type'] = 'numeric';
        $options['max']['type'] = 'numeric';

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
        // 1. Sanitize numeric representation
        $value = $this->sanitizeNumeric($value);
        
        if ($value === null) {
            $this->addError("The provided value is not a valid number.");
            return false;
        }

        // 2. Base validation via parent class
        if (false === parent::validate($value)) {
            return false;
        }

        // 3. Format validation
        if (!$this->validateFormat($value)) {
            return false;
        }

        // 4. Digit length validation
        if (!$this->validateLength($value)) {
            return false;
        }

        // 5. Decimal precision validation
        if (!$this->validatePrecision($value)) {
            return false;
        }

        // 6. Automatic rounding
        $this->applyRounding($value);

        // 7. Final value cleanup and sign/zero handling
        $value = $this->cleanValue($value);

        return $value !== null;
    }

    /**
     * Sanitizes raw input into a valid numeric representation.
     *
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeNumeric(mixed $value): mixed
    {
        // Check for null or empty string
        if (is_null($value) || $value === '') {
            return null;
        }

        // Return immediately if already numeric
        if (is_numeric($value)) {
            return $value;
        }

        // Process string representations
        if (is_string($value)) {
            $value = trim($value);
            
            // Remove thousands separators (spaces, commas)
            $value = str_replace([' ', ','], '', $value);
            
            // Normalize decimal comma to dot
            $value = str_replace(',', '.', $value);
            
            // Strip currency symbols and extra non-numeric characters
            $value = preg_replace('/[^0-9.\-+]/', '', $value);
            
            // Verify if sanitized string is numeric
            if (is_numeric($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Validates numeric formatting against specified format option.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateFormat(mixed $value): bool
    {
        $format = $this->options['format'] ?? null;

        if (!$format) {
            return true;
        }

        switch ($format) {
            case 'integer':
                if (!is_int($value) && !ctype_digit((string) $value)) {
                    $this->addError("The number must be an integer.");
                    return false;
                }
                break;

            case 'decimal':
                if (!is_float($value) && !preg_match('/^\d+\.\d+$/', (string) $value)) {
                    $this->addError("The number must be a decimal.");
                    return false;
                }
                break;

            case 'percentage':
                if (!is_numeric($value) || $value < 0 || $value > 100) {
                    $this->addError("The number must be a valid percentage between 0 and 100.");
                    return false;
                }
                break;

            case 'currency':
                if (!is_numeric($value) || $value < 0) {
                    $this->addError("The number must be a valid positive currency amount.");
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Validates and truncates total digit count length constraint.
     *
     * @param mixed &$value
     * @return bool
     */
    private function validateLength(mixed &$value): bool
    {
        $length = $this->options['length'] ?? null;

        if (!$length) {
            return true;
        }

        // Count digits (ignoring sign and decimal points)
        $number = (string) $value;
        $digits = preg_replace('/[^0-9]/', '', $number);
        $count = strlen($digits);

        if ($count > $length) {
            // Truncate length
            $value = substr((string) $value, 0, $length);
        }

        return true;
    }

    /**
     * Validates maximum allowed decimal precision.
     *
     * @param mixed &$value
     * @return bool
     */
    private function validatePrecision(mixed &$value): bool
    {
        $precision = $this->options['precision'] ?? null;

        if (!$precision) {
            return true;
        }

        if (!is_numeric($value)) {
            return false;
        }

        // Extract decimal places
        $number = (string) $value;
        $parts = explode('.', $number);
        $decimals = isset($parts[1]) ? strlen($parts[1]) : 0;

        if ($decimals > $precision) {
            $this->addError("The number must not have more than {$precision} decimal place(s).");
            return false;
        }

        return true;
    }

    /**
     * Applies rounding if requested in configuration options.
     *
     * @param mixed &$value
     * @return void
     */
    private function applyRounding(mixed &$value): void
    {
        $round = $this->options['round'] ?? null;

        if (!$round || !is_numeric($value)) {
            return;
        }

        $value = round($value, $round);
    }

    /**
     * Normalizes final numeric value and enforces zero/negative constraints.
     *
     * @param mixed $value
     * @return mixed
     */
    private function cleanValue(mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }

        // Cast whole floats to integer
        if (is_float($value) && $value == (int) $value) {
            return (int) $value;
        }

        // Handle negative sign constraint
        if (!($this->options['allowNegative'] ?? true)) {
            if ($value < 0) {
                $value = abs($value);
            }
        }

        // Handle zero allowance constraint
        if (!($this->options['allowZero'] ?? true)) {
            if ($value == 0) {
                $this->addError("The number cannot be equal to zero.");
                return null;
            }
        }

        return $value;
    }

    /**
     * Appends an error message to internal errors array and logs it.
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
     * Formats and returns the primary validation error message.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{min}', '{max}', '{length}', '{precision}'],
                [
                    $this->options['min'] ?? 'N/A',
                    $this->options['max'] ?? 'N/A',
                    $this->options['length'] ?? 'N/A',
                    $this->options['precision'] ?? 'N/A'
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The field '{$this->parameter}' must be a valid number.";
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

    /**
     * Extracts and cleans raw input into a sanitized numeric representation.
     *
     * @param mixed $value
     * @return mixed
     */
    public function getNumericValue(mixed $value): mixed
    {
        return $this->sanitizeNumeric($value);
    }
}