<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class FloatValidator
 *
 * Validates floating-point numbers with support for:
 * - Value ranges (min, max, range)
 * - Decimal precision checks
 * - Automatic value rounding
 * - Output type formatting
 * - Positive and negative sign enforcement
 * - Detailed error handling and customizable messages
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class FloatValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'float';

    /**
     * Internal array containing error messages captured during validation.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Configuration option definitions for float validation rules.
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
            // Minimum allowed value
            'min' => [
                'required' => false,
                'type' => 'float',
                'function' => fn(string $value) => (float) $value,
            ],

            // Maximum allowed value
            'max' => [
                'required' => false,
                'type' => 'float',
                'function' => fn(string $value) => (float) $value,
            ],

            // Allowed range (format: min-max)
            'range' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => (bool) preg_match('/^[0-9]+(\.[0-9]+)?-[0-9]+(\.[0-9]+)?$/', $value),
            ],

            // Precision (maximum number of decimal places)
            'precision' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value,
            ],

            // Rounding decimal places
            'round' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value,
            ],

            // Require strictly positive number
            'positive' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ],

            // Require strictly negative number
            'negative' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ],

            // Output data type format ('float' or 'string')
            'format' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['float', 'string'], true),
            ],

            // Custom error message override
            'message' => [
                'required' => false,
                'type' => 'string',
            ],
        ];
    }

    /**
     * Validates and processes a given numeric floating-point value.
     *
     * @param mixed &$value Target data reference to validate and format.
     * @return bool `true` on success, `false` on failure.
     */
    public function validate(mixed &$value): bool
    {
        $this->errors = [];

        // 1. Sanitize input to float representation
        $sanitizedValue = $this->sanitizeFloat($value);

        if ($sanitizedValue === null) {
            $this->addError("The provided value is not a valid floating-point number.");
            return false;
        }

        $value = $sanitizedValue;

        // 2. Validate numeric data type
        if (!is_float($value) && !is_numeric($value)) {
            $this->addError("The value must be a floating-point number.");
            return false;
        }

        // 3. Validate sign constraints (positive/negative)
        if (!$this->validateSign($value)) {
            return false;
        }

        // 4. Validate value ranges (min/max/range)
        if (!$this->validateRange($value)) {
            return false;
        }

        // 5. Validate decimal precision
        if (!$this->validatePrecision($value)) {
            return false;
        }

        // 6. Apply automatic rounding if configured
        $this->applyRounding($value);

        // 7. Apply output formatting
        $this->applyFormat($value);

        return true;
    }

    /**
     * Sanitizes raw input into a float representation.
     *
     * @param mixed $value
     * @return float|null Returns parsed float or `null` if invalid.
     */
    private function sanitizeFloat(mixed $value): ?float
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $value = trim($value);

            // Normalize decimal separator
            $value = str_replace(',', '.', $value);

            // Strip non-numeric and non-sign characters
            $value = preg_replace('/[^0-9.\-+]/', '', $value);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    /**
     * Validates sign constraints (positive or negative).
     *
     * @param float $value
     * @return bool
     */
    private function validateSign(float $value): bool
    {
        if ($this->options['positive'] ?? false) {
            if ($value <= 0) {
                $this->addError("The number must be positive (greater than 0).");
                return false;
            }
        }

        if ($this->options['negative'] ?? false) {
            if ($value >= 0) {
                $this->addError("The number must be negative (less than 0).");
                return false;
            }
        }

        return true;
    }

    /**
     * Validates minimum, maximum, and range bounds.
     *
     * @param float &$value
     * @return bool
     */
    private function validateRange(float &$value): bool
    {
        // Explicit Range (min-max)
        if ($range = $this->options['range'] ?? null) {
            [$min, $max] = explode('-', $range);
            $min = (float) $min;
            $max = (float) $max;

            if ($value < $min || $value > $max) {
                $this->addError("The number must be between {$min} and {$max}.");
                return false;
            }

            return true;
        }

        // Minimum threshold
        if (isset($this->options['min'])) {
            $min = (float) $this->options['min'];
            if ($value < $min) {
                if (isset($this->options['format'])) {
                    $value = $min;
                } else {
                    $this->addError("The number must not be less than {$min}.");
                    return false;
                }
            }
        }

        // Maximum threshold
        if (isset($this->options['max'])) {
            $max = (float) $this->options['max'];
            if ($value > $max) {
                if (isset($this->options['format'])) {
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
     * Validates decimal precision count.
     *
     * @param float $value
     * @return bool
     */
    private function validatePrecision(float $value): bool
    {
        $precision = $this->options['precision'] ?? null;

        if ($precision === null) {
            return true;
        }

        $string = (string) $value;
        $parts = explode('.', $string);
        $decimals = isset($parts[1]) ? strlen($parts[1]) : 0;

        if ($decimals > $precision) {
            $this->addError("The number must not have more than {$precision} decimal place(s).");
            return false;
        }

        return true;
    }

    /**
     * Applies precision rounding to the value reference.
     *
     * @param float|string &$value
     * @return void
     */
    private function applyRounding(float|string &$value): void
    {
        $round = $this->options['round'] ?? null;

        if ($round !== null) {
            $value = round((float) $value, $round);
        }
    }

    /**
     * Formats output representation based on configuration rules.
     *
     * @param float|string &$value
     * @return void
     */
    private function applyFormat(float|string &$value): void
    {
        $format = $this->options['format'] ?? null;

        if ($format === 'string') {
            $value = (string) $value;
        }
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
     * Generates a human-readable primary error message, substituting rule placeholders.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{min}', '{max}', '{range}', '{precision}'],
                [
                    $this->options['min'] ?? 'N/A',
                    $this->options['max'] ?? 'N/A',
                    $this->options['range'] ?? 'N/A',
                    $this->options['precision'] ?? 'N/A',
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The field '{$this->parameter}' must be a valid floating-point number.";
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
}