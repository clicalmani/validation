<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Validation\Rules\IDValidator;
use Clicalmani\Validation\Rules\IDsValidator;
use Validation\Test\TestCase;

/**
 * IDValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class IDValidatorTest extends TestCase
{
    private function check(string $class, string $pattern, mixed &$value): bool
    {
        return $this->rule($class, $pattern)->validate($value);
    }

    public function test_null_value_is_rejected_without_database(): void
    {
        $v = null;
        $this->assertFalse($this->check(IDValidator::class, 'id|model:user', $v));
    }

    public function test_empty_string_is_rejected_without_database(): void
    {
        $v = '';
        $this->assertFalse($this->check(IDValidator::class, 'id|model:user', $v));
    }

    public function test_model_option_is_required(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(IDValidator::class, 'id');
    }

    public function test_model_name_is_normalized_from_snake_case(): void
    {
        $rule = $this->rule(IDValidator::class, 'id|model:user_profile');
        $options = \Closure::bind(fn () => $this->options, $rule, IDValidator::class)->call($rule);
        $this->assertSame('UserProfile', $options['model']);
    }

    public function test_scope_option_accepts_json(): void
    {
        $rule = $this->rule(IDValidator::class, 'id|model:user|scope:{"status":1}');
        $options = \Closure::bind(fn () => $this->options, $rule, IDValidator::class)->call($rule);
        $this->assertSame(['status' => 1], $options['scope']);
    }

    public function test_with_trashed_flag(): void
    {
        $rule = $this->rule(IDValidator::class, 'id|model:user|withTrashed:true');
        $options = \Closure::bind(fn () => $this->options, $rule, IDValidator::class)->call($rule);
        $this->assertTrue($options['withTrashed']);
    }

    public function test_unknown_option_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(IDValidator::class, 'id|model:user|foo:bar');
    }

    public function test_ids_requires_model_option(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(IDsValidator::class, 'id[]');
    }

    public function test_ids_empty_value_is_rejected_without_database(): void
    {
        $v = null;
        $this->assertFalse($this->check(IDsValidator::class, 'id[]|model:user', $v));
    }
}