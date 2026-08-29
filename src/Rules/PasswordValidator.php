<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class PasswordValidator
 * 
 * Validates password strings with comprehensive support for:
 * - Length constraints (exact length, min length, max length)
 * - Complexity rules (uppercase, lowercase, numbers, symbols counts)
 * - Strength score evaluation (weak, medium, strong, very_strong)
 * - Blacklisted common passwords and sequential patterns
 * - Consecutive character repeat limits
 * - Space character prohibition
 * - Custom error messages and strength inspections
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class PasswordValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'password';

    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Recognized strength rating thresholds.
     *
     * @var array<int, string>
     */
    protected array $strengthLevels = ['weak', 'medium', 'strong', 'very_strong'];

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
            // Exact length requirement
            'length' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum character length
            'min' => [
                'required' => false,
                'type' => 'int',
                'default' => 8,
                'function' => fn(string $value) => (int) $value
            ],
            
            // Maximum character length
            'max' => [
                'required' => false,
                'type' => 'int',
                'default' => 255,
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum uppercase character count
            'uppercase' => [
                'required' => false,
                'type' => 'int',
                'default' => 1,
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum lowercase character count
            'lowercase' => [
                'required' => false,
                'type' => 'int',
                'default' => 1,
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum numeric character count
            'numbers' => [
                'required' => false,
                'type' => 'int',
                'default' => 1,
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum symbol character count
            'symbols' => [
                'required' => false,
                'type' => 'int',
                'default' => 0,
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum required password strength level (weak, medium, strong, very_strong)
            'strength' => [
                'required' => false,
                'type' => 'string',
                'default' => 'medium',
                'validator' => fn(string $value) => in_array($value, ['weak', 'medium', 'strong', 'very_strong'], true)
            ],
            
            // Common password and sequence check (true/false)
            'common' => [
                'required' => false,
                'type' => 'bool',
                'default' => true,
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Max consecutive repeated characters allowed (e.g. 2 = max 2 identical adjacent chars)
            'repeat' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Disallow spaces (true/false)
            'noSpace' => [
                'required' => false,
                'type' => 'bool',
                'default' => true,
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
     * Validates input password string against length, complexity, strength, and blacklists.
     *
     * @param mixed &$value Target password string.
     * @return bool `true` if all enabled validation rules pass, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        $value = $this->parseString($value);
        
        // 1. Data type check
        if (!is_string($value)) {
            $this->addError("The password must be a string.");
            return false;
        }

        // 2. Length check
        if (!$this->validateLength($value)) {
            return false;
        }

        // 3. Space prohibition check
        if ($this->options['noSpace'] ?? true) {
            if (str_contains($value, ' ')) {
                $this->addError("The password must not contain spaces.");
                return false;
            }
        }

        // 4. Consecutive repeated character check
        if (!$this->validateRepeats($value)) {
            return false;
        }

        // 5. Common password/sequence check
        if ($this->options['common'] ?? true) {
            if ($this->isCommonPassword($value)) {
                $this->addError("The password is too common. Please choose a more secure password.");
                return false;
            }
        }

        // 6. Character complexity check
        if (!$this->validateComplexity($value)) {
            return false;
        }

        // 7. Overall strength check
        if (!$this->validateStrength($value)) {
            return false;
        }

        // 8. Truncation sanitization (if max defined)
        if ($this->options['max'] ?? false) {
            $value = substr($value, 0, $this->options['max']);
        }

        return true;
    }

    /**
     * Validates character length constraints.
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
                $this->addError("The password must be exactly {$exactLength} characters long.");
                return false;
            }
            return true;
        }

        // Minimum length check
        if ($min = $this->options['min'] ?? 8) {
            if ($length < $min) {
                $this->addError("The password must be at least {$min} characters long.");
                return false;
            }
        }

        // Maximum length check
        if ($max = $this->options['max'] ?? 255) {
            if ($length > $max) {
                $this->addError("The password must not exceed {$max} characters.");
                return false;
            }
        }

        return true;
    }

    /**
     * Validates consecutive repeated character limits.
     *
     * @param string $value
     * @return bool
     */
    private function validateRepeats(string $value): bool
    {
        $repeatLimit = $this->options['repeat'] ?? null;

        if ($repeatLimit !== null) {
            $chars = str_split($value);
            $currentChar = null;
            $repeatCount = 0;

            foreach ($chars as $char) {
                if ($char === $currentChar) {
                    $repeatCount++;
                    if ($repeatCount > $repeatLimit) {
                        $this->addError("The password must not contain more than {$repeatLimit} consecutive identical character(s).");
                        return false;
                    }
                } else {
                    $currentChar = $char;
                    $repeatCount = 0;
                }
            }
        }

        return true;
    }

    /**
     * Validates character group complexity requirements.
     *
     * @param string $value
     * @return bool
     */
    private function validateComplexity(string $value): bool
    {
        $uppercase = $this->options['uppercase'] ?? 1;
        $lowercase = $this->options['lowercase'] ?? 1;
        $numbers = $this->options['numbers'] ?? 1;
        $symbols = $this->options['symbols'] ?? 0;

        $hasUppercase = preg_match_all('/[A-Z]/', $value);
        $hasLowercase = preg_match_all('/[a-z]/', $value);
        $hasNumbers = preg_match_all('/[0-9]/', $value);
        $hasSymbols = preg_match_all('/[^A-Za-z0-9]/', $value);

        if ($uppercase > 0 && $hasUppercase < $uppercase) {
            $this->addError("The password must contain at least {$uppercase} uppercase letter(s).");
            return false;
        }

        if ($lowercase > 0 && $hasLowercase < $lowercase) {
            $this->addError("The password must contain at least {$lowercase} lowercase letter(s).");
            return false;
        }

        if ($numbers > 0 && $hasNumbers < $numbers) {
            $this->addError("The password must contain at least {$numbers} digit(s).");
            return false;
        }

        if ($symbols > 0 && $hasSymbols < $symbols) {
            $this->addError("The password must contain at least {$symbols} symbol(s).");
            return false;
        }

        return true;
    }

    /**
     * Validates calculated password strength against required minimum level.
     *
     * @param string $value
     * @return bool
     */
    private function validateStrength(string $value): bool
    {
        $requiredStrength = $this->options['strength'] ?? 'medium';
        $actualStrength = $this->calculateStrength($value);

        $strengthOrder = [
            'weak' => 0,
            'medium' => 1,
            'strong' => 2,
            'very_strong' => 3
        ];

        if ($strengthOrder[$actualStrength] < $strengthOrder[$requiredStrength]) {
            $this->addError("The password strength is too weak. Minimum required strength: {$requiredStrength}.");
            return false;
        }

        return true;
    }

    /**
     * Calculates the overall strength level rating for a password string.
     *
     * @param string $value
     * @return string One of 'weak', 'medium', 'strong', 'very_strong'.
     */
    private function calculateStrength(string $value): string
    {
        $score = 0;
        $length = strlen($value);

        // Length score
        if ($length >= 8) $score++;
        if ($length >= 12) $score++;
        if ($length >= 16) $score++;

        // Complexity score
        if (preg_match('/[A-Z]/', $value)) $score++;
        if (preg_match('/[a-z]/', $value)) $score++;
        if (preg_match('/[0-9]/', $value)) $score++;
        if (preg_match('/[^A-Za-z0-9]/', $value)) $score++;

        // Character diversity score
        $uniqueChars = count(array_unique(str_split($value)));
        if ($uniqueChars > $length * 0.7) $score++;

        // Score evaluation mapping
        if ($score <= 3) return 'weak';
        if ($score <= 5) return 'medium';
        if ($score <= 7) return 'strong';
        return 'very_strong';
    }

    /**
     * Checks if the password matches common blacklisted passwords or basic patterns.
     *
     * @param string $value
     * @return bool
     */
    private function isCommonPassword(string $value): bool
    {
        // Common weak passwords dictionary
        $commonPasswords = [
            'password', '123456', '12345678', 'qwerty', 'abc123',
            'monkey', 'letmein', 'dragon', '111111', 'baseball',
            'iloveyou', 'trustno1', '123123', 'welcome', 'admin',
            'password1', 'passw0rd', 'password123', '123456789',
            'qwerty123', '12345', '1234567', '987654321', 'mypassword'
        ];

        // Exact match check
        if (in_array(strtolower($value), $commonPasswords, true)) {
            return true;
        }

        // Sequential pattern check
        $sequences = [
            '123', '234', '345', '456', '567', '678', '789', '890',
            'abc', 'bcd', 'cde', 'def', 'efg', 'fgh', 'ghi',
            'qwe', 'wer', 'ert', 'rty', 'tyu', 'yui', 'uio'
        ];

        foreach ($sequences as $seq) {
            if (str_contains(strtolower($value), $seq)) {
                return true;
            }
        }

        return false;
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
                ['{min}', '{max}', '{length}', '{strength}'],
                [
                    (string) ($this->options['min'] ?? 8),
                    (string) ($this->options['max'] ?? 255),
                    (string) ($this->options['length'] ?? 'N/A'),
                    (string) ($this->options['strength'] ?? 'medium')
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The password does not meet security requirements.";
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
     * Calculates and returns the strength rating for a target password string.
     *
     * @param string $value
     * @return string
     */
    public function getStrength(string $value): string
    {
        return $this->calculateStrength($value);
    }
}