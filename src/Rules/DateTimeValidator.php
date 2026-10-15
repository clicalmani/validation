<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rules\Traits\ValidatesFormatString;
use Clicalmani\Validation\ValidatesOption;
use DateTime;
use DateTimeZone;
use Exception;

use function Clicalmani\Validation\validateFormatString;

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
class DateTimeValidator extends DateBase
{
    use ValidatesOption;

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
                'function' => fn(string $value) => array_map('trim', explode(';', $value))
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
        $this->parsedDate = null;
        $this->validFormat = null;

        // Travailler sur une copie : $dateTime n'est modifié qu'en cas de succès
        $input = $this->normalizeDateTime($dateTime);

        if ($input === null) {
            $this->addError("The provided date/time is invalid or empty.");
            return false;
        }

        if (($this->options['relative'] ?? false) && $this->isRelativeDate($input)) {
            if (!$this->validateRelativeDateTime($input)) {
                return false;
            }
        } elseif (!$this->validateFormat($input, $this->options['strict'] ?? true)) {
            return false;
        }

        // Les bornes s'appliquent aussi aux expressions relatives
        if (!$this->validateRange()) {
            return false;
        }

        $dateTime = $this->parsedDate->format($this->validFormat ?? 'Y-m-d H:i:s');

        return true;
    }

    /**
     * Checks calendar and clock integrity strictly.
     *
     * @param string $dateTime Raw input value.
     * @return bool
     */
    private function validateStrict(string $dateTime): bool
    {
        if (!$this->parsedDate) {
            return false;
        }

        $parsedString = $this->parsedDate->format($this->validFormat ?? 'Y-m-d H:i:s');
        
        if ($parsedString !== $dateTime && !$this->isFormatEquivalent($dateTime, $parsedString)) {
            $this->addError("The date/time '{$dateTime}' is invalid.");
            return false;
        }

        return true;
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
    public function getparsedDate(): ?DateTime
    {
        return $this->parsedDate;
    }

    private function normalizeDateTime(mixed $dateTime): ?string
    {
        if (is_int($dateTime) || is_float($dateTime)) {
            // Interpréter le timestamp dans le fuseau configuré, pas dans celui de date()
            return (new DateTime('@' . (int) $dateTime))
                ->setTimezone($this->timezone())
                ->format('Y-m-d H:i:s');
        }

        if ($dateTime instanceof \DateTimeInterface) {
            return $dateTime->format('Y-m-d H:i:s');
        }

        return is_string($dateTime) ? trim($dateTime) : null;
    }

    private function validateFormat(string $dateTime, bool $strict): bool
    {
        $formats = $this->getFormats();
        $timezone = $this->timezone();

        foreach ($formats as $format) {
            // '!' remet les champs non parsés à l'epoch (sinon 'Y-m-d' reçoit l'heure courante)
            $dt = DateTime::createFromFormat('!' . $format, $dateTime, $timezone);

            if ($dt === false) {
                continue;
            }

            // PHP >= 8.2 : false quand il n'y a rien à signaler
            $errors = DateTime::getLastErrors() ?: ['warning_count' => 0, 'error_count' => 0];

            if ($errors['error_count'] > 0) {
                continue;
            }

            // Mode strict : pas de débordement (30 février) et aller-retour identique
            if ($strict && ($errors['warning_count'] > 0 || $dt->format($format) !== $dateTime)) {
                continue;
            }

            $this->parsedDate = $dt;
            $this->validFormat = $format;

            return true;
        }

        $this->addError($this->getFormatErrorMessage($dateTime, $formats));
        return false;
    }

    private function validateRelativeDateTime(string $dateTime): bool
    {
        try {
            $this->parsedDate = new DateTime($dateTime, $this->timezone());
            $this->validFormat = 'Y-m-d H:i:s';
            return true;
        } catch (Exception $e) {
            $this->addError("The relative date/time '{$dateTime}' is invalid.");
            return false;
        }
    }
}