<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class StringValidator
 * 
 * Validates string values with comprehensive support for:
 * - Length constraints (min, max, exact length)
 * - Built-in formats (email, url, uuid, ip, alpha, alnum, numeric, slug)
 * - Custom regular expressions (pattern)
 * - Character restrictions (noSpace, noSpecial)
 * - Automatic sanitization (trim, lower, upper, ucfirst, ucwords, strip_tags, htmlspecialchars)
 * - Detailed error reporting and custom message replacements
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class StringValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'string';

    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Stores the processed/sanitized value.
     *
     * @var string
     */
    protected string $sanitizedValue = '';

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
            // Exact string length requirement
            'length' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum character length
            'min' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Maximum character length
            'max' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Regular expression pattern validation
            'pattern' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => @preg_match($value, 'test') !== false
            ],
            
            // Predefined string formats (email, url, uuid, ip, alpha, alnum, numeric, slug)
            'format' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['email', 'url', 'uuid', 'ip', 'alpha', 'alnum', 'numeric', 'slug'], true)
            ],
            
            // Value sanitization filter (trim, lower, upper, ucfirst, ucwords, strip_tags, htmlspecialchars)
            'sanitize' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['trim', 'lower', 'upper', 'ucfirst', 'ucwords', 'strip_tags', 'htmlspecialchars'], true)
            ],
            
            // Disallow spaces (true/false)
            'noSpace' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Disallow special characters (true/false)
            'noSpecial' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Custom failure error message template override
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input string against length, format, pattern, and sanitization rules.
     *
     * @param mixed &$value Target input value to validate and sanitize.
     * @return bool `true` if all enabled validation rules pass, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Cast input to string
        $value = $this->castToString($value);
        
        if ($value === null) {
            $this->addError("The provided value cannot be converted to a string.");
            return false;
        }

        // 2. Apply sanitization filters
        $this->applySanitize($value);

        // 3. Validate string length constraints
        if (!$this->validateLength($value)) {
            return false;
        }

        // 4. Validate space restriction
        if ($this->options['noSpace'] ?? false) {
            if (str_contains($value, ' ')) {
                $this->addError("The string must not contain spaces.");
                return false;
            }
        }

        // 5. Validate special character restriction
        if ($this->options['noSpecial'] ?? false) {
            if (preg_match('/[^a-zA-Z0-9\s]/', $value)) {
                $this->addError("The string must not contain special characters.");
                return false;
            }
        }

        // 6. Validate format constraints
        if (!$this->validateFormat($value)) {
            return false;
        }

        // 7. Validate regex pattern
        if (!$this->validatePattern($value)) {
            return false;
        }

        // 8. Update original variable reference with sanitized value
        $value = $this->sanitizedValue;

        return true;
    }

    /**
     * Converts raw input to a string representation.
     *
     * @param mixed $value
     * @return string|null
     */
    private function castToString(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Applies requested sanitization function to input value.
     *
     * @param string &$value
     * @return void
     */
    private function applySanitize(string &$value): void
    {
        $sanitize = $this->options['sanitize'] ?? null;

        if (!$sanitize) {
            $this->sanitizedValue = $value;
            return;
        }

        switch ($sanitize) {
            case 'trim':
                $this->sanitizedValue = trim($value);
                break;
            case 'lower':
                $this->sanitizedValue = strtolower($value);
                break;
            case 'upper':
                $this->sanitizedValue = strtoupper($value);
                break;
            case 'ucfirst':
                $this->sanitizedValue = ucfirst(strtolower($value));
                break;
            case 'ucwords':
                $this->sanitizedValue = ucwords(strtolower($value));
                break;
            case 'strip_tags':
                $this->sanitizedValue = strip_tags($value);
                break;
            case 'htmlspecialchars':
                $this->sanitizedValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                break;
            default:
                $this->sanitizedValue = $value;
        }

        // Re-apply max length truncation if defined
        if ($max = $this->options['max'] ?? null) {
            $this->sanitizedValue = substr($this->sanitizedValue, 0, $max);
        }
    }

    /**
     * Validates string length options (exact length, min, max).
     *
     * @param string $value
     * @return bool
     */
    private function validateLength(string $value): bool
    {
        $length = strlen($value);

        // Exact length check
        if ($exactLength = $this->options['length'] ?? null) {
            if ($length !== $exactLength) {
                $this->addError("The string must be exactly {$exactLength} characters long.");
                return false;
            }
            return true;
        }

        // Minimum length check
        if ($min = $this->options['min'] ?? null) {
            if ($length < $min) {
                $this->addError("The string must be at least {$min} characters long.");
                return false;
            }
        }

        // Maximum length check
        if ($max = $this->options['max'] ?? null) {
            if ($length > $max) {
                $this->addError("The string must not exceed {$max} characters.");
                return false;
            }
            
            // Truncate if sanitization is not specified
            if (!isset($this->options['sanitize'])) {
                $this->sanitizedValue = substr($value, 0, $max);
            }
        }

        return true;
    }

    /**
     * Validates string against predefined formats.
     *
     * @param string $value
     * @return bool
     */
    private function validateFormat(string $value): bool
    {
        $format = $this->options['format'] ?? null;

        if (!$format) {
            return true;
        }

        $valid = match ($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'uuid' => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1,
            'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'alpha' => preg_match('/^[a-zA-Z]+$/', $value) === 1,
            'alnum' => preg_match('/^[a-zA-Z0-9]+$/', $value) === 1,
            'numeric' => is_numeric($value),
            'slug' => preg_match('/^[a-z0-9-]+$/', $value) === 1,
            default => true
        };

        if (!$valid) {
            $this->addError("The string does not match the '{$format}' format.");
            return false;
        }

        return true;
    }

    /**
     * Validates string against custom regex pattern.
     *
     * @param string $value
     * @return bool
     */
    private function validatePattern(string $value): bool
    {
        $pattern = $this->options['pattern'] ?? null;

        if (!$pattern) {
            return true;
        }

        if (!@preg_match($pattern, $value)) {
            $this->addError("The string does not match the expected pattern.");
            return false;
        }

        return true;
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
                ['{value}', '{min}', '{max}', '{length}'],
                [
                    $this->parameter,
                    $this->options['min'] ?? 'N/A',
                    $this->options['max'] ?? 'N/A',
                    $this->options['length'] ?? 'N/A'
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The string '{$this->parameter}' is invalid.";
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
     * Gets the sanitized string result after validation execution.
     *
     * @return string
     */
    public function getSanitizedValue(): string
    {
        return $this->sanitizedValue;
    }
}