<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\NumberValidator;
use Validation\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * NumberValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class NumberValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(NumberValidator::class, $pattern)->validate($value);
    }

    public static function validNumbers(): array
    {
        return [[42], [12.5], ['42'], ['  12.5 '], ['1,5'], ['+3'], ['-7'], ['2.5e3']];
    }

    #[DataProvider('validNumbers')]
    public function test_valid_numbers(mixed $in): void
    {
        $v = $in;
        $this->assertTrue($this->check('number', $v));
    }

    public static function invalidNumbers(): array
    {
        return [['abc'], [null], [''], [[]], ['12,3,4']];
    }

    #[DataProvider('invalidNumbers')]
    public function test_invalid_numbers(mixed $in): void
    {
        $v = $in;
        $this->assertFalse($this->check('number', $v));
    }

    public function test_type_positive(): void
    {
        $v = 5;
        $this->assertTrue($this->check('number|type:positive', $v));
        $v = 0;
        $this->assertFalse($this->check('number|type:positive', $v));
    }

    public function test_type_negative(): void
    {
        $v = -1.5;
        $this->assertTrue($this->check('number|type:negative', $v));
        $v = 0;
        $this->assertFalse($this->check('number|type:negative', $v));
    }

    public function test_type_integer(): void
    {
        $v = '42';
        $this->assertTrue($this->check('number|type:integer', $v));
        $v = 4.2;
        $this->assertFalse($this->check('number|type:integer', $v));
    }

    public function test_type_even_odd(): void
    {
        $v = 4;
        $this->assertTrue($this->check('number|type:even', $v));
        $v = 3;
        $this->assertFalse($this->check('number|type:even', $v));
        $this->assertTrue($this->check('number|type:odd', $v));
    }

    public function test_range_is_inclusive(): void
    {
        $v = 10;
        $this->assertTrue($this->check('number|range:1-10', $v));
        $v = 10.5;
        $this->assertFalse($this->check('number|range:1-10', $v));
    }

    public function test_min_max_reject_out_of_bounds(): void
    {
        $v = 5;
        $this->assertFalse($this->check('number|min:10', $v));
        $v = 20;
        $this->assertFalse($this->check('number|max:10', $v));
    }

    public function test_invalid_range_format_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(NumberValidator::class, 'number|range:abc-def');
    }

    public function test_invalid_type_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(NumberValidator::class, 'number|type:complex');
    }

    public function test_round_applies(): void
    {
        $v = 3.14159;
        $this->assertTrue($this->check('number|round:2', $v));
        $this->assertSame(3.14, $v);
    }

    public function test_format_casts_output(): void
    {
        $v = '42';
        $this->assertTrue($this->check('number|format:int', $v));
        $this->assertSame(42, $v);

        $v = '42';
        $this->assertTrue($this->check('number|format:float', $v));
        $this->assertSame(42.0, $v);
    }

    public function test_invalid_format_option_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(NumberValidator::class, 'number|format:money');
    }
}