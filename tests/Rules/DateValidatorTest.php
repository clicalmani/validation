<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\DateValidator;
use Validation\Test\TestCase;

/**
 * DateValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class DateValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(DateValidator::class, $pattern)->validate($value);
    }

    public function test_valid_default_format(): void
    {
        $v = '2026-10-15';
        $this->assertTrue($this->check('date', $v));
        $this->assertSame('2026-10-15', $v);
    }

    public function test_datetime_string_is_rejected_by_default(): void
    {
        $v = '2026-10-15 12:30:00';
        $this->assertFalse($this->check('date', $v));
    }

    public function test_explicit_format(): void
    {
        $v = '15/10/2026';
        $this->assertTrue($this->check('date|format:d/m/Y', $v));
        $this->assertSame('15/10/2026', $v);
    }

    public function test_multiple_formats(): void
    {
        $v = '15.10.2026';
        $this->assertTrue($this->check('date|formats:Y-m-d d.m.Y d/m/Y', $v));
    }

    public function test_impossible_date_is_rejected(): void
    {
        $v = '2026-02-30';
        $this->assertFalse($this->check('date', $v));
    }

    public function test_loose_mode_accepts_rollover(): void
    {
        $v = '2026-02-30';
        $this->assertTrue($this->check('date|strict:false', $v));
    }

    public function test_min_boundary_is_inclusive(): void
    {
        $v = '2026-01-01';
        $this->assertTrue($this->check('date|min:2026-01-01', $v));
    }

    public function test_max_boundary(): void
    {
        $v = '2027-01-01';
        $this->assertFalse($this->check('date|max:2026-12-31', $v));
    }

    public function test_relative_expression(): void
    {
        $v = 'today';
        $this->assertTrue($this->check('date|relative:true', $v));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $v);
    }

    public function test_relative_expression_respects_max(): void
    {
        $v = '+10 year';
        $this->assertFalse($this->check('date|relative:true|max:+1 year', $v));
    }

    public function test_unix_timestamp_accepted(): void
    {
        $v = 1700000000;
        $this->assertTrue($this->check('date', $v));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $v);
    }

    public function test_null_is_rejected(): void
    {
        $v = null;
        $this->assertFalse($this->check('date', $v));
    }

    public function test_invalid_timezone_option_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(DateValidator::class, 'date|timezone:Mars/Base');
    }
}