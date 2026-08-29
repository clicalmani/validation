<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class DateValidator
 *
 * Validates date inputs with comprehensive support for:
 * - Single or multiple format specifications (e.g., Y-m-d, d/m/Y, etc.)
 * - Range constraints (minimum and maximum bound checks)
 * - Custom timezones
 * - Relative date evaluation (e.g., 'now', '+1 day', 'next week')
 * - Calendar validity and day/month consistency checks
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class DateValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'date';
    
    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Parsed DateTime instance generated after successful validation.
     *
     * @var DateTime|null
     */
    protected ?DateTime $parsedDate = null;

    /**
     * The specific format string that successfully parsed the input date.
     *
     * @var string|null
     */
    protected ?string $validFormat = null;

    /**
     * Returns the array configuration schema for supported rule options.
     *
     * @return array<string, array{
     *     required: bool,
     *     type: string,
     *     default?: mixed,
     *     validator?: callable,
     *     function?: callable
     * }>
     */
    public function options(): array
    {
        return [
            // Target date format string (supports Y, m, d, H, i, s, etc.)
            'format' => [
                'required' => false,
                'type' => 'string',
                'default' => 'Y-m-d',
                'validator' => fn(string $value) => $this->validateFormatString($value)
            ],
            
            // Multiple acceptable date formats
            'formats' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('trim', explode(' ', $value))
            ],
            
            // Minimum allowed date (absolute date string or relative expression)
            'min' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Maximum allowed date (absolute date string or relative expression)
            'max' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Evaluation timezone identifier
            'timezone' => [
                'required' => false,
                'type' => 'string',
                'default' => config('app.timezone'),
                'validator' => fn(string $value) => in_array($value, DateTimeZone::listIdentifiers(), true)
            ],
            
            // Flag to enable relative date expressions ('now', '+1 day', etc.)
            'relative' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Strict calendar validation flag (verifies true day/month compatibility)
            'strict' => [
                'required' => false,
                'type' => 'bool',
                'default' => true,
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Custom error message template
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates the input date value against configured format, strictness, and range constraints.
     *
     * @param mixed &$date The target date input to validate. Mutated to formatted string upon success.
     * @return bool `true` if all enabled validation steps pass, `false` otherwise.
     */
    public function validate(mixed &$date): bool
    {
        // 1. Input normalization
        $date = $this->normalizeDate($date);
        
        if ($date === null) {
            $this->addError("The provided date is invalid or empty.");
            return false;
        }

        // 2. Relative date processing
        if ($this->options['relative'] ?? false) {
            if ($this->isRelativeDate($date)) {
                return $this->validateRelativeDate($date);
            }
        }

        // 3. Format matching
        if (!$this->validateFormat($date)) {
            return false;
        }

        // 4. Calendar strictness check (month/day boundary validation)
        if ($this->options['strict'] ?? true) {
            if (!$this->validateStrict($date)) {
                return false;
            }
        }

        // 5. Min/Max range bounds check
        if (!$this->validateRange($date)) {
            return false;
        }

        // 6. Mutate input payload to standardized date string
        $date = $this->parsedDate?->format($this->validFormat ?? 'Y-m-d') ?? $date;

        return true;
    }

    /**
     * Normalizes heterogeneous date inputs into a standardized string.
     *
     * @param mixed $date Input value (int timestamp, DateTime instance, or string).
     * @return string|null Normalized date string, or `null` if unrecognized format.
     */
    private function normalizeDate(mixed $date): ?string
    {
        if (is_null($date)) {
            return null;
        }

        if (is_int($date) || is_float($date)) {
            return date('Y-m-d', (int) $date);
        }

        if ($date instanceof DateTime) {
            return $date->format('Y-m-d');
        }

        if (is_string($date)) {
            return trim($date);
        }

        return null;
    }

    /**
     * Attempts to parse the date string against allowed format configurations.
     *
     * @param string &$date Target date string.
     * @return bool `true` if a format matched strictly without warnings, `false` otherwise.
     */
    private function validateFormat(string &$date): bool
    {
        $formats = $this->getFormats();
        $timezone = $this->options['timezone'] ?? config('app.timezone');
        $parsed = false;

        foreach ($formats as $format) {
            try {
                $dt = DateTime::createFromFormat($format, $date, new DateTimeZone($timezone));
                
                if ($dt !== false) {
                    $errors = DateTime::getLastErrors();
                    if ($errors && $errors['warning_count'] === 0 && $errors['error_count'] === 0) {
                        $this->parsedDate = $dt;
                        $this->validFormat = $format;
                        $parsed = true;
                        break;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        if (!$parsed) {
            $this->addError($this->getFormatErrorMessage($date, $formats));
            return false;
        }

        return true;
    }

    /**
     * Resolves candidate date formats to test during format validation.
     *
     * @return array<int, string>
     */
    private function getFormats(): array
    {
        if (!empty($this->options['formats'])) {
            return $this->options['formats'];
        }

        $format = $this->options['format'] ?? 'Y-m-d';
        
        $commonFormats = [
            'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y',
            'Y/m/d', 'Y.m.d', 'd.m.Y', 'm.d.Y',
            'Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s',
            'Y-M-d', 'M d Y', 'd M Y', 'M d, Y'
        ];

        if (in_array($format, $commonFormats, true)) {
            return $commonFormats;
        }

        return [$format];
    }

    /**
     * Verifies that logical month/day values in parsed dates match original strings strictly.
     *
     * @param string $date Original input string.
     * @return bool
     */
    private function validateStrict(string $date): bool
    {
        if (!$this->parsedDate) {
            return false;
        }

        $parsedString = $this->parsedDate->format($this->validFormat ?? 'Y-m-d');
        
        if ($parsedString !== $date && !$this->isFormatEquivalent($date, $parsedString)) {
            $this->addError("The date '{$date}' is invalid (day/month mismatch).");
            return false;
        }

        return true;
    }

    /**
     * Evaluates minimum and maximum allowed range boundaries.
     *
     * @param string $date Target date string.
     * @return bool
     */
    private function validateRange(string $date): bool
    {
        if (!$this->parsedDate) {
            return false;
        }

        // Min boundary
        if ($min = $this->options['min'] ?? null) {
            $minDate = $this->parseDateRange($min);
            if ($minDate && $this->parsedDate < $minDate) {
                $this->addError("The date must be after " . $minDate->format('Y-m-d'));
                return false;
            }
        }

        // Max boundary
        if ($max = $this->options['max'] ?? null) {
            $maxDate = $this->parseDateRange($max);
            if ($maxDate && $this->parsedDate > $maxDate) {
                $this->addError("The date must be before " . $maxDate->format('Y-m-d'));
                return false;
            }
        }

        return true;
    }

    /**
     * Converts absolute or relative boundary strings into DateTime instances.
     *
     * @param string $value Boundary expression (e.g., '2026-01-01', '+1 week', 'now').
     * @return DateTime|null
     */
    private function parseDateRange(string $value): ?DateTime
    {
        try {
            if (str_starts_with($value, '+') || str_starts_with($value, '-') || $value === 'now') {
                return new DateTime($value);
            }
            
            $formats = $this->getFormats();
            foreach ($formats as $format) {
                $dt = DateTime::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt;
                }
            }
            
            return new DateTime($value);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Checks if a given input string matches common relative date syntax patterns.
     *
     * @param string $date Candidate expression string.
     * @return bool
     */
    private function isRelativeDate(string $date): bool
    {
        $relativePatterns = [
            '/^now$/i',
            '/^today$/i',
            '/^yesterday$/i',
            '/^tomorrow$/i',
            '/^[+-]\d+\s+(day|week|month|year)s?$/i',
            '/^next\s+(day|week|month|year)$/i',
            '/^last\s+(day|week|month|year)$/i'
        ];

        foreach ($relativePatterns as $pattern) {
            if (preg_match($pattern, $date)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluates relative date strings into parsed DateTime objects.
     *
     * @param string &$date Target relative date string.
     * @return bool
     */
    private function validateRelativeDate(string &$date): bool
    {
        try {
            $dt = new DateTime($date);
            $this->parsedDate = $dt;
            $this->validFormat = 'Y-m-d';
            $date = $dt->format('Y-m-d');
            return true;
        } catch (Exception $e) {
            $this->addError("The relative date '{$date}' is invalid.");
            return false;
        }
    }

    /**
     * Compares raw numeric components of two date strings to determine equality.
     *
     * @param string $date1
     * @param string $date2
     * @return bool
     */
    private function isFormatEquivalent(string $date1, string $date2): bool
    {
        $clean1 = preg_replace('/[^0-9]/', '', $date1);
        $clean2 = preg_replace('/[^0-9]/', '', $date2);
        return $clean1 === $clean2;
    }

    /**
     * Validates character safety of custom format option strings.
     *
     * @param string $format Format string to check.
     * @return bool
     */
    private function validateFormatString(string $format): bool
    {
        $allowedChars = ['Y', 'm', 'd', 'H', 'i', 's', ' '];
        $allowedSeparators = ['-', '/', '.', ':', ' '];
        
        $clean = str_replace($allowedSeparators, '', $format);
        $chars = str_split($clean);
        
        foreach ($chars as $char) {
            if (!in_array($char, $allowedChars, true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Appends an error message to the internal collection and logs it.
     *
     * @param string $message Error message string.
     * @return void
     */
    private function addError(string $message): void
    {
        $this->errors[] = $message;
        $this->log($message);
    }

    /**
     * Constructs the format error message using custom templates or defaults.
     *
     * @param string $date Target date string.
     * @param array<int, string> $formats Target format strings list.
     * @return string
     */
    private function getFormatErrorMessage(string $date, array $formats): string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}', '{formats}'],
                [$date, implode(', ', $formats)],
                $customMessage
            );
        }

        return "The date '{$date}' does not match any supported format: " . implode(', ', $formats);
    }

    /**
     * Retrieves the primary (first) error message encountered during validation.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        return $this->errors[0] ?? null;
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
     * Gets the parsed DateTime object produced during validation execution.
     *
     * @return DateTime|null
     */
    public function getParsedDate(): ?DateTime
    {
        return $this->parsedDate;
    }
}