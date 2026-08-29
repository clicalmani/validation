<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class DateTimeValidator
 *
 * Validates date and time inputs with comprehensive support for:
 * - Multiple date/time formats (e.g., 'Y-m-d H:i:s', 'd/m/Y H:i', ISO 8601, etc.)
 * - Timezone configurations
 * - Range constraints (minimum and maximum bounds)
 * - Relative date/time expressions ('now', '+1 day', 'next week')
 * - Custom format parsing with time components
 * - Strict integrity checking (day/month/time consistency)
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class DateTimeValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'datetime';
    
    /**
     * Collection of error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Parsed DateTime instance generated upon successful validation.
     *
     * @var DateTime|null
     */
    protected ?DateTime $parsedDateTime = null;

    /**
     * The exact format string that successfully parsed the input payload.
     *
     * @var string|null
     */
    protected ?string $validFormat = null;

    /**
     * Returns the configuration schema for supported validation rule options.
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
            // Target date/time format string
            'format' => [
                'required' => false,
                'type' => 'string',
                'default' => 'Y-m-d H:i:s',
                'validator' => fn(string $value) => $this->validateFormatString($value)
            ],
            
            // Multiple candidate date/time formats
            'formats' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('trim', explode('|', $value))
            ],
            
            // Minimum allowed date/time boundary (absolute or relative)
            'min' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Maximum allowed date/time boundary (absolute or relative)
            'max' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Timezone identifier for date evaluation
            'timezone' => [
                'required' => false,
                'type' => 'string',
                'default' => config('app.timezone'),
                'validator' => fn(string $value) => in_array($value, DateTimeZone::listIdentifiers(), true)
            ],
            
            // Flag allowing relative date expressions ('now', '+1 day', etc.)
            'relative' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Strict calendar/clock consistency validation flag
            'strict' => [
                'required' => false,
                'type' => 'bool',
                'default' => true,
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Custom failure message template override
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input values against configured formats, timezones, and range constraints.
     *
     * @param mixed &$dateTime The target date/time input. Mutated to formatted string upon success.
     * @return bool `true` if all enabled validation steps pass, `false` otherwise.
     */
    public function validate(mixed &$dateTime): bool
    {
        // 1. Input normalization
        $dateTime = $this->normalizeDateTime($dateTime);
        
        if ($dateTime === null) {
            $this->addError("The provided date/time is invalid or empty.");
            return false;
        }

        // 2. Relative date/time evaluation
        if ($this->options['relative'] ?? false) {
            if ($this->isRelativeDateTime($dateTime)) {
                return $this->validateRelativeDateTime($dateTime);
            }
        }

        // 3. Format validation
        if (!$this->validateFormat($dateTime)) {
            return false;
        }

        // 4. Strict consistency check
        if ($this->options['strict'] ?? true) {
            if (!$this->validateStrict($dateTime)) {
                return false;
            }
        }

        // 5. Min/Max range bounds check
        if (!$this->validateRange($dateTime)) {
            return false;
        }

        // 6. Mutate input payload to standardized date/time string
        if ($this->parsedDateTime) {
            $dateTime = $this->parsedDateTime->format($this->validFormat ?? 'Y-m-d H:i:s');
        }

        return true;
    }

    /**
     * Normalizes heterogeneous inputs into a clean string representation.
     *
     * @param mixed $dateTime Input value (timestamp, DateTime object, or string).
     * @return string|null Standardized string or `null` on invalid types.
     */
    private function normalizeDateTime(mixed $dateTime): ?string
    {
        if (is_null($dateTime)) {
            return null;
        }

        if (is_int($dateTime) || is_float($dateTime)) {
            return date('Y-m-d H:i:s', (int) $dateTime);
        }

        if ($dateTime instanceof DateTime) {
            return $dateTime->format('Y-m-d H:i:s');
        }

        if (is_string($dateTime)) {
            return trim($dateTime);
        }

        return null;
    }

    /**
     * Validates the date/time format against candidate schemas.
     *
     * @param string &$dateTime Target input string.
     * @return bool
     */
    private function validateFormat(string &$dateTime): bool
    {
        $formats = $this->getFormats();
        $timezone = $this->options['timezone'] ?? config('app.timezone');
        $parsed = false;

        foreach ($formats as $format) {
            try {
                $dt = DateTime::createFromFormat($format, $dateTime, new DateTimeZone($timezone));
                
                if ($dt !== false) {
                    $errors = DateTime::getLastErrors();
                    if ($errors && $errors['warning_count'] === 0 && $errors['error_count'] === 0) {
                        // Ensure exact output format match to prevent loose parsing
                        $test = $dt->format($format);
                        if ($test === $dateTime) {
                            $this->parsedDateTime = $dt;
                            $this->validFormat = $format;
                            $parsed = true;
                            break;
                        }
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        if (!$parsed) {
            $this->addError($this->getFormatErrorMessage($dateTime, $formats));
            return false;
        }

        return true;
    }

    /**
     * Resolves candidate date/time formats to evaluate.
     *
     * @return array<int, string>
     */
    private function getFormats(): array
    {
        if (!empty($this->options['formats'])) {
            return $this->options['formats'];
        }

        $format = $this->options['format'] ?? 'Y-m-d H:i:s';
        
        // Predefined fallback format configurations
        $commonFormats = [
            'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
            'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
            'm/d/Y H:i:s', 'm/d/Y H:i', 'm/d/Y',
            'Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP',
            'd-m-Y H:i:s', 'm-d-Y H:i:s',
            'Y-m-d H:i:s.u', 'Y-m-d H:i:sP'
        ];

        if (in_array($format, $commonFormats, true)) {
            return $commonFormats;
        }

        return [$format];
    }

    /**
     * Checks calendar and clock integrity strictly.
     *
     * @param string $dateTime Raw input value.
     * @return bool
     */
    private function validateStrict(string $dateTime): bool
    {
        if (!$this->parsedDateTime) {
            return false;
        }

        $parsedString = $this->parsedDateTime->format($this->validFormat ?? 'Y-m-d H:i:s');
        
        if ($parsedString !== $dateTime && !$this->isFormatEquivalent($dateTime, $parsedString)) {
            $this->addError("The date/time '{$dateTime}' is invalid.");
            return false;
        }

        return true;
    }

    /**
     * Evaluates minimum and maximum range boundaries.
     *
     * @param string $dateTime Raw input value.
     * @return bool
     */
    private function validateRange(string $dateTime): bool
    {
        if (!$this->parsedDateTime) {
            return false;
        }

        // Min boundary check
        if ($min = $this->options['min'] ?? null) {
            $minDate = $this->parseDateTimeRange($min);
            if ($minDate && $this->parsedDateTime < $minDate) {
                $this->addError("The date/time must be after " . $minDate->format('Y-m-d H:i:s'));
                return false;
            }
        }

        // Max boundary check
        if ($max = $this->options['max'] ?? null) {
            $maxDate = $this->parseDateTimeRange($max);
            if ($maxDate && $this->parsedDateTime > $maxDate) {
                $this->addError("The date/time must be before " . $maxDate->format('Y-m-d H:i:s'));
                return false;
            }
        }

        return true;
    }

    /**
     * Parses absolute or relative range boundary strings into DateTime instances.
     *
     * @param string $value Expression string (e.g., '2026-01-01 00:00:00', '+1 day', 'now').
     * @return DateTime|null
     */
    private function parseDateTimeRange(string $value): ?DateTime
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
     * Determines whether an input string matches relative syntax patterns.
     *
     * @param string $dateTime Candidate expression.
     * @return bool
     */
    private function isRelativeDateTime(string $dateTime): bool
    {
        $relativePatterns = [
            '/^now$/i',
            '/^today$/i',
            '/^yesterday$/i',
            '/^tomorrow$/i',
            '/^[+-]\d+\s+(second|minute|hour|day|week|month|year)s?$/i',
            '/^next\s+(day|week|month|year)$/i',
            '/^last\s+(day|week|month|year)$/i'
        ];

        foreach ($relativePatterns as $pattern) {
            if (preg_match($pattern, $dateTime)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluates a relative date expression into a standard DateTime representation.
     *
     * @param string &$dateTime Target relative string.
     * @return bool
     */
    private function validateRelativeDateTime(string &$dateTime): bool
    {
        try {
            $dt = new DateTime($dateTime);
            $this->parsedDateTime = $dt;
            $this->validFormat = 'Y-m-d H:i:s';
            $dateTime = $dt->format('Y-m-d H:i:s');
            return true;
        } catch (Exception $e) {
            $this->addError("The relative date/time '{$dateTime}' is invalid.");
            return false;
        }
    }

    /**
     * Checks numeric component equivalence between two date strings.
     *
     * @param string $datetime1
     * @param string $datetime2
     * @return bool
     */
    private function isFormatEquivalent(string $datetime1, string $datetime2): bool
    {
        $clean1 = preg_replace('/[^0-9]/', '', $datetime1);
        $clean2 = preg_replace('/[^0-9]/', '', $datetime2);
        return $clean1 === $clean2;
    }

    /**
     * Validates individual characters in a custom format string.
     *
     * @param string $format
     * @return bool
     */
    private function validateFormatString(string $format): bool
    {
        $allowedChars = ['Y', 'm', 'd', 'H', 'i', 's', ' '];
        $allowedSeparators = ['-', '/', '.', ':', 'T', ' ', '+'];
        
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
     * Appends an error message to the internal list and logs it.
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
     * Constructs a formatted failure error message string.
     *
     * @param string $dateTime
     * @param array<int, string> $formats
     * @return string
     */
    private function getFormatErrorMessage(string $dateTime, array $formats): string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}', '{formats}'],
                [$dateTime, implode(', ', $formats)],
                $customMessage
            );
        }

        return "The date/time '{$dateTime}' does not match any supported format: " . implode(', ', $formats);
    }

    /**
     * Gets the first error message recorded during validation.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        return $this->errors[0] ?? null;
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
     * Gets the parsed DateTime instance generated during validation.
     *
     * @return DateTime|null
     */
    public function getParsedDateTime(): ?DateTime
    {
        return $this->parsedDateTime;
    }
}