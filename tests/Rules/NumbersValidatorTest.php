<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\NumbersValidator;
use Validation\Test\TestCase;

/**
 * NumbersValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class NumbersValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(NumbersValidator::class, $pattern)->validate($value);
    }

    public function test_accepts_array_of_numbers(): void
    {
        $v = [1, '2.5', 3];
        $this->assertTrue($this->check('number[]', $v));
        $this->assertSame([1, '2.5', 3], $v);
    }

    public function test_accepts_delimited_string(): void
    {
        $v = '1, 2, 3';
        $this->assertTrue($this->check('number[]', $v));
        $this->assertSame(['1', '2', '3'], $v);
    }

    public function test_accepts_json_array(): void
    {
        $v = '[1,2,3]';
        $this->assertTrue($this->check('number[]', $v));
        $this->assertSame([1, 2, 3], $v);
    }

    public function test_wraps_single_numeric_scalar(): void
    {
        $v = 5;
        $this->assertTrue($this->check('number[]', $v));
        $this->assertSame([5], $v);
    }

    public function test_rejects_non_numeric_item(): void
    {
        $v = [1, 'abc', 3];
        $this->assertFalse($this->check('number[]', $v));
    }

    public function test_rejects_unsupported_input(): void
    {
        $v = null;
        $this->assertFalse($this->check('number[]', $v));
    }

    public function test_min_items(): void
    {
        $v = [1];
        $this->assertFalse($this->check('number[]|minItems:2', $v));
        $v = [1, 2];
        $this->assertTrue($this->check('number[]|minItems:2', $v));
    }

    public function test_max_items(): void
    {
        $v = [1, 2, 3];
        $this->assertFalse($this->check('number[]|maxItems:2', $v));
    }

    public function test_distinct_deduplicates(): void
    {
        $v = [1, 1, 2];
        $this->assertTrue($this->check('number[]|distinct:true', $v));
        $this->assertSame([1, 2], $v);
    }

    public function test_join_outputs_string(): void
    {
        $v = [1, 2, 3];
        $this->assertTrue($this->check('number[]|join:true', $v));
        $this->assertSame('1,2,3', $v);
    }

    public function test_join_with_custom_separator(): void
    {
        $v = '1;2;3';
        $this->assertTrue($this->check('number[]|join:true|separator:;', $v));
        $this->assertSame('1;2;3', $v);
    }

    public function test_per_item_min_max(): void
    {
        $v = [5, 15];
        $this->assertFalse($this->check('number[]|min:10', $v));

        $v = [10, 15];
        $this->assertTrue($this->check('number[]|min:10', $v));
    }

    public function test_per_item_range(): void
    {
        $v = [1, 2, 99];
        $this->assertFalse($this->check('number[]|range:1-10', $v));
    }

    public function test_per_item_type(): void
    {
        $v = [1, 2.5];
        $this->assertFalse($this->check('number[]|type:integer', $v));
    }

    public function test_empty_items_are_filtered_by_default(): void
    {
        $v = ['1', '', '3'];
        $this->assertTrue($this->check('number[]', $v));
        $this->assertSame(['1', '3'], $v);
    }
}