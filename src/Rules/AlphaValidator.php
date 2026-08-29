<?php

namespace Clicalmani\Validation\Rules;

/**
 * Class AlphaValidator
 *
 * Validates alphabetic strings with customizable support for:
 * - Basic letters (A-Z, a-z) and Unicode characters (\p{L})
 * - Whitespace, dashes, apostrophes, and underscores
 * - Accents and diacritics
 * - Length constraints (via parent StringValidator)
 * - Detailed error handling and message customization
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class AlphaValidator extends StringValidator
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'alpha';

    /**
     * Internal array containing error messages captured during validation.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Configuration option definitions for alphabetic validation rules.
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
        $options = parent::options();

        $options['allowSpace'] = [
            'required' => false,
            'type' => 'bool',
            'default' => false,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        ];

        $options['allowDash'] = [
            'required' => false,
            'type' => 'bool',
            'default' => false,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        ];

        $options['allowApostrophe'] = [
            'required' => false,
            'type' => 'bool',
            'default' => false,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        ];

        $options['allowAccent'] = [
            'required' => false,
            'type' => 'bool',
            'default' => false,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        ];

        $options['allowUnderscore'] = [
            'required' => false,
            'type' => 'bool',
            'default' => false,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        ];

        $options['unicode'] = [
            'required' => false,
            'type' => 'bool',
            'default' => false,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        ];

        // Format option is not applicable to alphabetic validation
        unset($options['format']);

        return $options;
    }

    /**
     * Validates an alphabetic string input against configured criteria.
     *
     * @param mixed &$value Target data reference to validate.
     * @return bool `true` on success, `false` on failure.
     */
    public function validate(mixed &$value): bool
    {
        $this->errors = [];

        // 1. String conversion/parsing
        $parsedValue = $this->parseString($value);

        if ($parsedValue === null) {
            $this->addError("The provided value is not a valid string.");
            return false;
        }

        $value = $parsedValue;

        // 2. Validate string length via parent StringValidator
        if (!parent::validate($value)) {
            return false;
        }

        // 3. Build regex pattern based on options
        $pattern = $this->buildPattern();

        // 4. Validate string matching pattern
        if (!preg_match($pattern, $value)) {
            $this->addError($this->getErrorMessage($value));
            return false;
        }

        return true;
    }

    /**
     * Constructs the regular expression pattern based on configured options.
     *
     * @return string
     */
    private function buildPattern(): string
    {
        $allowSpace = $this->options['allowSpace'] ?? false;
        $allowDash = $this->options['allowDash'] ?? false;
        $allowApostrophe = $this->options['allowApostrophe'] ?? false;
        $allowAccent = $this->options['allowAccent'] ?? false;
        $allowUnderscore = $this->options['allowUnderscore'] ?? false;
        $unicode = $this->options['unicode'] ?? false;

        $chars = '';

        // Base letters
        if ($unicode) {
            $chars .= '\p{L}';
        } else {
            $chars .= 'a-zA-Z';
        }

        // Spaces
        if ($allowSpace) {
            $chars .= '\s';
        }

        // Dash (-)
        if ($allowDash) {
            $chars .= '-';
        }

        // Apostrophe (')
        if ($allowApostrophe) {
            $chars .= "'";
        }

        // Accents / Diacritics
        if ($allowAccent) {
            if ($unicode) {
                $chars .= '\p{M}';
            } else {
                $chars .= 'À-ÿ';
            }
        }

        // Underscore (_)
        if ($allowUnderscore) {
            $chars .= '_';
        }

        return '/^[' . $chars . ']+$/u';
    }

    /**
     * Generates a descriptive error message for an invalid string input.
     *
     * @param string $value
     * @return string
     */
    private function getErrorMessage(string $value): string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}', '{allowed}'],
                [$value, $this->getAllowedCharacters()],
                $customMessage
            );
        }

        $allowed = $this->getAllowedCharacters();
        $suffix = $allowed !== '' ? " with {$allowed}" : '';

        return "The string '{$value}' must contain only letters{$suffix}.";
    }

    /**
     * Formats a descriptive text list of allowed non-letter character types.
     *
     * @return string
     */
    private function getAllowedCharacters(): string
    {
        $parts = [];

        if ($this->options['allowSpace'] ?? false) {
            $parts[] = 'spaces';
        }

        if ($this->options['allowDash'] ?? false) {
            $parts[] = 'dashes (-)';
        }

        if ($this->options['allowApostrophe'] ?? false) {
            $parts[] = 'apostrophes (\')';
        }

        if ($this->options['allowAccent'] ?? false) {
            $parts[] = 'accents';
        }

        if ($this->options['allowUnderscore'] ?? false) {
            $parts[] = 'underscores (_)';
        }

        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);

        return implode(', ', $parts) . ' and ' . $last;
    }

    /**
     * Records a validation error and logs it internally.
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

        return $this->errors[0] ?? "The field '{$this->parameter}' must contain only letters.";
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