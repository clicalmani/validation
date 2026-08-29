<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class RegExpValidator
 * 
 * Validates string inputs against regular expression patterns with support for:
 * - Custom regular expressions and pattern verification
 * - Evaluation flags (case-insensitivity, multiline, unicode, etc.)
 * - Input sanitization (trimming, case conversion, tag stripping)
 * - Result inversion (negative matching)
 * - Regex match capturing
 * - Detailed PCRE error reporting
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class RegExpValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'regexp';
    
    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * The sanitized value after successful validation.
     *
     * @var string
     */
    protected string $validatedValue = '';

    /**
     * Captured regular expression matches (when 'capture' option is enabled).
     *
     * @var array<int|string, mixed>
     */
    protected array $matches = [];

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
            // Target regular expression pattern (without wrapping delimiters)
            'pattern' => [
                'required' => true,
                'type' => 'string',
                'validator' => fn(string $value) => $this->validatePattern($value)
            ],
            
            // Pattern modifier flags (e.g. i, m, s, u)
            'flags' => [
                'required' => false,
                'type' => 'string',
                'default' => '',
                'validator' => fn(string $value) => $this->validateFlags($value)
            ],
            
            // Value sanitization filter prior to regex evaluation
            'sanitize' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['trim', 'lower', 'upper', 'strip_tags'], true)
            ],
            
            // Capture sub-pattern matches array flag
            'capture' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Invert evaluation logic (must NOT match pattern)
            'not' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Custom error message override
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input string value against configured regular expression pattern.
     *
     * @param mixed &$value Reference to the input value being validated.
     * @return bool `true` if regex evaluation succeeds, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Sanitize raw value into string format
        $value = $this->sanitizeValue($value);
        
        if ($value === null) {
            $this->addError("The provided value is not a valid string.");
            return false;
        }

        // 2. Build full regex string with delimiters and flags
        $pattern = $this->options['pattern'];
        $flags = $this->options['flags'] ?? '';
        $delimiter = $this->detectDelimiter($pattern);

        $fullPattern = $delimiter . $pattern . $delimiter . $flags;

        // 3. Execute regular expression match operation
        $result = preg_match($fullPattern, $value, $matches);

        // 4. Handle PCRE engine errors
        if ($result === false) {
            $error = preg_last_error();
            $this->addError("Regular expression engine error: " . $this->getRegexError($error));
            return false;
        }

        // 5. Evaluate pattern match outcome
        $isValid = ($result === 1);

        // 6. Invert evaluation logic if 'not' flag is configured
        if ($this->options['not'] ?? false) {
            $isValid = !$isValid;
        }

        if (!$isValid) {
            $this->addError($this->getErrorMessage($value));
            return false;
        }

        // 7. Store captured matches if requested
        if ($this->options['capture'] ?? false) {
            $this->matches = $matches;
        }

        $this->validatedValue = $value;

        return true;
    }

    /**
     * Sanitizes raw input into string representation before validation.
     *
     * @param mixed $value
     * @return string|null
     */
    private function sanitizeValue(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value) ?: null;
        }

        $value = (string) $value;

        // Apply string transformation function
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
            case 'strip_tags':
                $value = strip_tags($value);
                break;
        }

        return $value;
    }

    /**
     * Detects delimiter used at start of pattern string or defaults to slash.
     *
     * @param string $pattern
     * @return string
     */
    private function detectDelimiter(string $pattern): string
    {
        $delimiters = ['/', '#', '~', '|', '@', '%', '!', '`'];
        
        foreach ($delimiters as $delimiter) {
            if (str_starts_with($pattern, $delimiter)) {
                return $delimiter;
            }
        }
        
        return '/';
    }

    /**
     * Validates structural syntax of regular expression pattern string.
     *
     * @param string $pattern
     * @return bool
     */
    private function validatePattern(string $pattern): bool
    {
        if (empty($pattern)) {
            return false;
        }

        $delimiter = $this->detectDelimiter($pattern);
        $testPattern = $delimiter . $pattern . $delimiter;
        
        return @preg_match($testPattern, 'test') !== false;
    }

    /**
     * Validates regex modifier flags string against PCRE allowed set.
     *
     * @param string $flags
     * @return bool
     */
    private function validateFlags(string $flags): bool
    {
        $allowedFlags = ['i', 'm', 's', 'x', 'u', 'U', 'A', 'D', 'S', 'J'];
        $flagChars = str_split($flags);
        
        foreach ($flagChars as $char) {
            if (!in_array($char, $allowedFlags, true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Resolves human-readable description for PCRE error code.
     *
     * @param int $error PCRE error code constant.
     * @return string
     */
    private function getRegexError(int $error): string
    {
        return match ($error) {
            PREG_NO_ERROR => 'No error',
            PREG_INTERNAL_ERROR => 'PCRE internal error',
            PREG_BACKTRACK_LIMIT_ERROR => 'Backtrack limit reached',
            PREG_RECURSION_LIMIT_ERROR => 'Recursion limit reached',
            PREG_BAD_UTF8_ERROR => 'Malformed UTF-8 string error',
            PREG_BAD_UTF8_OFFSET_ERROR => 'Invalid UTF-8 offset error',
            PREG_JIT_STACKLIMIT_ERROR => 'JIT stack limit reached',
            default => 'Unknown PCRE error',
        };
    }

    /**
     * Constructs contextual validation error message.
     *
     * @param string $value
     * @return string
     */
    private function getErrorMessage(string $value): string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}', '{pattern}'],
                [$value, $this->options['pattern']],
                $customMessage
            );
        }

        $notMessage = ($this->options['not'] ?? false) ? " must not" : " must";

        return "The value '{$value}'{$notMessage} match the pattern: " . $this->options['pattern'];
    }

    /**
     * Appends error message to tracking array and records log entry.
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
     * Resolves and returns validation error message with placeholder replacements.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}', '{pattern}'],
                [$this->parameter, $this->options['pattern']],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The field '{$this->parameter}' does not match the expected pattern.";
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
     * Returns captured regex matches resulting from execution.
     *
     * @return array<int|string, mixed>
     */
    public function getMatches(): array
    {
        return $this->matches;
    }

    /**
     * Returns the sanitized value produced during validation.
     *
     * @return string
     */
    public function getValidatedValue(): string
    {
        return $this->validatedValue;
    }

    /**
     * Evaluates whether a given string matches the configured pattern independently.
     *
     * @param string $value
     * @return bool
     */
    public function test(string $value): bool
    {
        $pattern = $this->options['pattern'];
        $flags = $this->options['flags'] ?? '';
        $delimiter = $this->detectDelimiter($pattern);
        $fullPattern = $delimiter . $pattern . $delimiter . $flags;

        return preg_match($fullPattern, $value) === 1;
    }
}