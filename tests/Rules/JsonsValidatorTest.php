<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\JsonsValidator;
use Validation\Test\TestCase;

/**
 * JsonsValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class JsonsValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(JsonsValidator::class, $pattern)->validate($value);
    }

    public function test_accepts_array_of_json_strings(): void
    {
        $v = ['{"a":1}', '{"b":2}'];
        $this->assertTrue($this->check('json[]', $v));
        $this->assertSame([['a' => 1], ['b' => 2]], $v);
    }

    public function test_accepts_json_array_payload(): void
    {
        $v = '[{"a":1},{"b":2}]';
        $this->assertTrue($this->check('json[]', $v));
        $this->assertCount(2, $v);
    }

    public function test_rejects_invalid_json_item(): void
    {
        $v = ['{"a":1}', '{oops'];
        $this->assertFalse($this->check('json[]', $v));
    }

    public function test_rejects_non_json_string_item(): void
    {
        $v = ['text'];
        $this->assertFalse($this->check('json[]', $v));
    }

    public function test_accepts_native_items(): void
    {
        $v = [['a' => 1], 42, '{"b":2}'];
        $this->assertTrue($this->check('json[]', $v));
        $this->assertSame([['a' => 1], 42, ['b' => 2]], $v);
    }

    public function test_min_items(): void
    {
        $v = ['{"a":1}'];
        $this->assertFalse($this->check('json[]|minItems:2', $v));
    }

    public function test_max_items(): void
    {
        $v = ['{"a":1}', '{"b":2}', '{"c":3}'];
        $this->assertFalse($this->check('json[]|maxItems:2', $v));
    }

    public function test_distinct_deduplicates(): void
    {
        $v = ['{"a":1}', '{"a":1}', '{"b":2}'];
        $this->assertTrue($this->check('json[]|distinct:true', $v));
        $this->assertCount(2, $v);
    }

    public function test_empty_items_are_filtered_by_default(): void
    {
        $v = ['{"a":1}', '', '{"b":2}'];
        $this->assertTrue($this->check('json[]', $v));
        $this->assertCount(2, $v);
    }
}