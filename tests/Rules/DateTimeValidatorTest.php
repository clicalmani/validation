<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Validation\Rules\DateTimeValidator;
use Validation\Test\TestCase;

/**
 * DateTimeValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class DateTimeValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(DateTimeValidator::class, $pattern)->validate($value);
    }

    public function test_valid_default_format(): void
    {
        $v = '2026-10-15 12:30:00';
        $this->assertTrue($this->check('datetime', $v)); // ROUGE sur PHP >= 8.2 (getLastErrors() === false)
        $this->assertSame('2026-10-15 12:30:00', $v);
    }

    public function test_impossible_date_is_rejected(): void
    {
        $v = '2026-02-30 10:00:00';
        $this->assertFalse($this->check('datetime', $v));
    }

    public function test_explicit_format_is_respected(): void
    {
        $v = '2026-10-15 12:00:00';
        $this->assertFalse($this->check('datetime|format:Y-m-d', $v)); // ROUGE : Y-m-d est dans $commonFormats
        $v = '2026-10-15';
        $this->assertTrue($this->check('datetime|format:Y-m-d', $v));
    }

    public function test_min_boundary_is_inclusive_for_date_only(): void
    {
        $v = '2026-01-01';
        $this->assertTrue($this->check('datetime|format:Y-m-d|min:2026-01-01', $v)); // ROUGE : heure courante injectée
    }

    public function test_max_boundary(): void
    {
        $v = '2027-01-01';
        $this->assertFalse($this->check('datetime|format:Y-m-d|max:2026-12-31', $v));
    }

    public function test_relative_expression(): void
    {
        $v = '+1 day';
        $this->assertTrue($this->check('datetime|relative:true', $v));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v);
    }

    public function test_relative_expression_respects_max(): void
    {
        $v = '+10 year';
        $this->assertFalse($this->check('datetime|relative:true|max:+1 year', $v)); // ROUGE : return anticipé
    }

    public function test_unix_timestamp_accepted(): void
    {
        $v = 1700000000;
        $this->assertTrue($this->check('datetime', $v));
    }

    public function test_null_is_rejected(): void
    {
        $v = null;
        $this->assertFalse($this->check('datetime', $v));
    }

    public function test_invalid_timezone_option_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(DateTimeValidator::class, 'datetime|timezone:Mars/Base');
    }
}