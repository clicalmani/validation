<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\NumericValidator;
use Validation\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * NumericValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class NumericValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(NumericValidator::class, $pattern)->validate($value);
    }

    public static function validNumerics(): array
    {
        return [[42], [12.5], ['42'], ['  12.5 '], ['1,5'], ['-7']];
    }

    #[DataProvider('validNumerics')]
    public function test_valid_numerics(mixed $in): void
    {
        $v = $in;
        $this->assertTrue($this->check('numeric', $v));
    }

    public static function invalidNumerics(): array
    {
        return [['abc'], [null], [''], [[]], ['1.2.3']];
    }

    #[DataProvider('invalidNumerics')]
    public function test_invalid_numerics(mixed $in): void
    {
        $v = $in;
        $this->assertFalse($this->check('numeric', $v));
    }

    public function test_format_percentage_bounds(): void
    {
        $v = 99.5;
        $this->assertTrue($this->check('numeric|format:percentage', $v));
        $v = 101;
        $this->assertFalse($this->check('numeric|format:percentage', $v));
    }

    public function test_format_currency_rejects_negative(): void
    {
        $v = 10.5;
        $this->assertTrue($this->check('numeric|format:currency', $v));
        $v = -1;
        $this->assertFalse($this->check('numeric|format:currency', $v));
    }

    public function test_format_decimal_requires_fraction(): void
    {
        $v = '12.5';
        $this->assertTrue($this->check('numeric|format:decimal', $v));
        $v = '12';
        $this->assertFalse($this->check('numeric|format:decimal', $v));
    }

    public function test_precision_limits_decimals(): void
    {
        $v = '1.234';
        $this->assertFalse($this->check('numeric|precision:2', $v));
        $v = '1.23';
        $this->assertTrue($this->check('numeric|precision:2', $v));
    }

    public function test_round_applies(): void
    {
        $v = '1.234';
        $this->assertTrue($this->check('numeric|round:1', $v));
        $this->assertSame(1.2, $v);
    }

    public function test_allow_negative_false_absolutizes(): void
    {
        $v = '-2.5';
        $this->assertTrue($this->check('numeric|allowNegative:false', $v));
        $this->assertSame(2.5, $v);
    }

    public function test_allow_zero_false_rejects_zero(): void
    {
        $v = 0;
        $this->assertFalse($this->check('numeric|allowZero:false', $v));
    }

    public function test_whole_floats_are_cast_to_int(): void
    {
        $v = '12.0';
        $this->assertTrue($this->check('numeric', $v));
        $this->assertSame(12, $v);
    }
}