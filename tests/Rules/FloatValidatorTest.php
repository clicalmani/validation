<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\FloatValidator;
use Validation\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * FloatValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class FloatValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(FloatValidator::class, $pattern)->validate($value);
    }

    public static function validFloats(): array
    {
        return [[1.5], ['1.5'], [' 2.75 '], ['1,5'], [-0.5], [42], ['42']];
    }

    #[DataProvider('validFloats')]
    public function test_valid_floats(mixed $in): void
    {
        $v = $in;
        $this->assertTrue($this->check('float', $v));
    }

    public static function invalidFloats(): array
    {
        return [['abc'], [null], [''], [[]], ['1.2.3']];
    }

    #[DataProvider('invalidFloats')]
    public function test_invalid_floats(mixed $in): void
    {
        $v = $in;
        $this->assertFalse($this->check('float', $v));
    }

    public function test_value_is_cast_to_float(): void
    {
        $v = '1,5';
        $this->assertTrue($this->check('float', $v));
        $this->assertSame(1.5, $v);
    }

    public function test_positive_constraint(): void
    {
        $v = 0;
        $this->assertFalse($this->check('float|positive:true', $v));
        $v = 0.1;
        $this->assertTrue($this->check('float|positive:true', $v));
    }

    public function test_negative_constraint(): void
    {
        $v = -0.1;
        $this->assertTrue($this->check('float|negative:true', $v));
        $v = 0;
        $this->assertFalse($this->check('float|negative:true', $v));
    }

    public function test_min_max(): void
    {
        $v = 0.5;
        $this->assertFalse($this->check('float|min:1.0', $v));
        $v = 9.9;
        $this->assertFalse($this->check('float|max:5.0', $v));
        $v = 2.5;
        $this->assertTrue($this->check('float|min:1.0|max:5.0', $v));
    }

    public function test_range(): void
    {
        $v = 5.5;
        $this->assertTrue($this->check('float|range:1-10', $v));
        $v = 50.5;
        $this->assertFalse($this->check('float|range:1-10', $v));
    }

    public function test_precision_limits_decimals(): void
    {
        $v = 1.234;
        $this->assertFalse($this->check('float|precision:2', $v));
        $v = 1.23;
        $this->assertTrue($this->check('float|precision:2', $v));
    }

    public function test_round(): void
    {
        $v = 3.14159;
        $this->assertTrue($this->check('float|round:2', $v));
        $this->assertSame(3.14, $v);
    }

    public function test_format_string(): void
    {
        $v = 1.5;
        $this->assertTrue($this->check('float|format:string', $v));
        $this->assertSame('1.5', $v);
    }

    public function test_invalid_format_option_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(FloatValidator::class, 'float|format:int');
    }
}