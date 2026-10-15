<?php
namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;
use DateTime;
use DateTimeZone;

/**
 * DateBase class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
class DateBase extends Rule
{
    /**
     * Parsed DateTime instance generated upon successful validation.
     *
     * @var DateTime|null
     */
    protected ?DateTime $parsedDate = null;

    protected function validateRange(): bool
    {
        if ($min = $this->options['min'] ?? null) {
            $minDate = $this->parseDateRange($min);
            if ($minDate && $this->parsedDate < $minDate) {
                $this->addError("The date/time must be on or after " . $minDate->format('Y-m-d H:i:s'));
                return false;
            }
        }

        if ($max = $this->options['max'] ?? null) {
            $maxDate = $this->parseDateRange($max);
            if ($maxDate && $this->parsedDate > $maxDate) {
                $this->addError("The date/time must be on or before " . $maxDate->format('Y-m-d H:i:s'));
                return false;
            }
        }

        return true;
    }

    protected function parseDateRange(string $value): ?DateTime
    {
        $timezone = $this->timezone();

        try {
            if (str_starts_with($value, '+') || str_starts_with($value, '-') || $value === 'now') {
                return new DateTime($value, $timezone);
            }

            foreach ($this->getFormats() as $format) {
                $dt = DateTime::createFromFormat('!' . $format, $value, $timezone);
                if ($dt !== false) {
                    return $dt;
                }
            }

            return new DateTime($value, $timezone);
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
    protected function isRelativeDate(string $dateTime): bool
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
     * Resolves candidate date formats to test during format validation.
     *
     * @return array<int, string>
     */
    protected function getFormats(): array
    {
        if (!empty($this->options['formats'])) {
            return $this->options['formats'];
        }
        return [$this->options['format'] ?? 'Y-m-d'];
    }

    protected function timezone(): DateTimeZone
    {
        return new DateTimeZone($this->options['timezone'] ?? config('app.timezone'));
    }
}