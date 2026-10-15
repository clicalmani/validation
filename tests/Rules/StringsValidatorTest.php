<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\StringsValidator;
use Validation\Test\TestCase;

/**
 * StringsValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class StringsValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(StringsValidator::class, $pattern)->validate($value);
    }

    public function test_accepts_array_of_strings(): void
    {
        $v = ['a', 'b'];
        $this->assertTrue($this->check('string[]', $v));
        $this->assertSame(['a', 'b'], $v);
    }

    public function test_accepts_delimited_string(): void
    {
        $v = 'a, b, c';
        $this->assertTrue($this->check('string[]', $v));
        $this->assertSame(['a', 'b', 'c'], $v);
    }

    public function test_rejects_unsupported_input(): void
    {
        $v = null;
        $this->assertFalse($this->check('string[]', $v));
    }

    public function test_empty_items_filtered_by_default(): void
    {
        $v = ['a', '', 'b'];
        $this->assertTrue($this->check('string[]', $v));
        $this->assertSame(['a', 'b'], $v);
    }

    public function test_allow_empty_keeps_empty_items(): void
    {
        $v = ['a', ''];
        $this->assertTrue($this->check('string[]|allowEmpty:true', $v));
        $this->assertSame(['a', ''], $v);
    }

    public function test_min_items(): void
    {
        $v = ['a'];
        $this->assertFalse($this->check('string[]|minItems:2', $v));
    }

    public function test_max_items(): void
    {
        $v = ['a', 'b', 'c'];
        $this->assertFalse($this->check('string[]|maxItems:2', $v));
    }

    public function test_distinct_deduplicates(): void
    {
        $v = ['a', 'a', 'b'];
        $this->assertTrue($this->check('string[]|distinct:true', $v));
        $this->assertSame(['a', 'b'], $v);
    }

    public function test_join_outputs_string(): void
    {
        $v = ['a', 'b'];
        $this->assertTrue($this->check('string[]|join:true', $v));
        $this->assertSame('a,b', $v);
    }

    public function test_per_item_max_length(): void
    {
        $v = ['abc', 'toolong'];
        $this->assertFalse($this->check('string[]|max:5', $v));
    }

    public function test_per_item_sanitize(): void
    {
        $v = ['  a  ', '  b  '];
        $this->assertTrue($this->check('string[]|sanitize:trim', $v));
        $this->assertSame(['a', 'b'], $v);
    }
}