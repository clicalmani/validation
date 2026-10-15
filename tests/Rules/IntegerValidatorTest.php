<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\IntegerValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * IntegerValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class IntegerValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(IntegerValidator::class, $pattern)->validate($value);
    }

    public static function validIntegers(): array
    {
        return [[42], ['42'], [' 42 '], ['1,000'], [0], ['007']];
    }

    #[DataProvider('validIntegers')]
    public function test_valid_integers(mixed $in): void
    {
        $v = $in;
        $this->assertTrue($this->check('integer', $v));
    }

    public function test_value_is_cast_to_int(): void
    {
        $v = '42';
        $this->assertTrue($this->check('integer', $v));
        $this->assertSame(42, $v);
    }

    public static function invalidIntegers(): array
    {
        return [[12.5], ['12.5'], ['abc'], [null], [''], [[]]];
    }

    #[DataProvider('invalidIntegers')]
    public function test_invalid_integers(mixed $in): void
    {
        $v = $in;
        $this->assertFalse($this->check('integer', $v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_negative_integer_is_accepted_by_default(): void
    {
        $v = '-5';
        $this->assertTrue($this->check('integer', $v));
        $this->assertSame(-5, $v);
    }

    public function test_allow_negative_false_rejects_negative(): void
    {
        $v = '-5';
        $this->assertFalse($this->check('integer|allowNegative:false', $v));
        $this->assertNotEmpty(Validator::errors('field'));
        $this->assertSame('-5', $v);
    }

    public function test_allow_negative_false_accepts_positive_and_zero(): void
    {
        $v = '5';
        $this->assertTrue($this->check('integer|allowNegative:false', $v));
        $this->assertSame(5, $v);

        $v = '0';
        $this->assertTrue($this->check('integer|allowNegative:false', $v));
        $this->assertSame(0, $v);
    }

    public function test_allow_zero_false_rejects_zero(): void
    {
        $v = '0';
        $this->assertFalse($this->check('integer|allowZero:false', $v));
    }

    public function test_min_max_boundaries(): void
    {
        $v = '3';
        $this->assertTrue($this->check('integer|min:3|max:10', $v));
        $v = '11';
        $this->assertFalse($this->check('integer|min:3|max:10', $v));
    }

    public function test_thousands_separators_are_stripped(): void
    {
        $v = '1 000';
        $this->assertTrue($this->check('integer', $v));
        $this->assertSame(1000, $v);
    }

    public function test_unknown_option_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(IntegerValidator::class, 'integer|precision:2');
    }
}