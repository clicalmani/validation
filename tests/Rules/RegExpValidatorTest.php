<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Validation\Rules\RegExpValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;

/**
 * RegExpValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class RegExpValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(RegExpValidator::class, $pattern)->validate($value);
    }

    public function test_pattern_option_is_required(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(RegExpValidator::class, 'regexp');
    }

    public function test_basic_match(): void
    {
        $v = 'hello123';
        $this->assertTrue($this->check('regexp|pattern:^[a-z0-9]+$', $v));
    }

    public function test_no_match_is_rejected(): void
    {
        $v = 'Hello 123';
        $this->assertFalse($this->check('regexp|pattern:^[a-z0-9]+$', $v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_flags_case_insensitive(): void
    {
        $v = 'HELLO';
        $this->assertTrue($this->check('regexp|pattern:^hello$|flags:i', $v));
    }

    public function test_invalid_flag_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(RegExpValidator::class, 'regexp|pattern:^abc$|flags:z');
    }

    public function test_not_inverts_result(): void
    {
        $v = 'HELLO';
        $this->assertTrue($this->check('regexp|pattern:^hello$|not:true', $v));
        $v = 'hello';
        $this->assertFalse($this->check('regexp|pattern:^hello$|not:true', $v));
    }

    public function test_sanitize_lower_before_match(): void
    {
        $v = 'HELLO';
        $this->assertTrue($this->check('regexp|pattern:^hello$|sanitize:lower', $v));
    }

    public function test_capture_stores_matches(): void
    {
        $rule = $this->rule(RegExpValidator::class, 'regexp|pattern:^(\d{4})-(\d{2})$|capture:true');
        $v = '2026-10';
        $this->assertTrue($rule->validate($v));
        $this->assertSame('2026', $rule->getMatches()[1]);
        $this->assertSame('10', $rule->getMatches()[2]);
    }

    public function test_null_input_is_rejected(): void
    {
        $v = null;
        $this->assertFalse($this->check('regexp|pattern:^.+$', $v));
    }

    public function test_empty_pattern_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(RegExpValidator::class, 'regexp|pattern:');
    }

    public function test_test_helper(): void
    {
        $rule = $this->rule(RegExpValidator::class, 'regexp|pattern:^abc$|flags:i');
        $this->assertTrue($rule->test('ABC'));
        $this->assertFalse($rule->test('xyz'));
    }
}