<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Validation\Rules\StringValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * StringValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class StringValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(StringValidator::class, $pattern)->validate($value);
    }

    public function test_simple_string_passes(): void
    {
        $v = 'hello';
        $this->assertTrue($this->check('string', $v));
        $this->assertSame('hello', $v);
    }

    public function test_null_is_rejected(): void
    {
        $v = null;
        $this->assertFalse($this->check('string', $v));
    }

    public function test_scalars_are_cast_to_string(): void
    {
        $v = 42;
        $this->assertTrue($this->check('string', $v));
        $this->assertSame('42', $v);

        $v = true;
        $this->assertTrue($this->check('string', $v));
        $this->assertSame('1', $v);
    }

    public function test_min_length(): void
    {
        $v = 'ab';
        $this->assertFalse($this->check('string|min:3', $v));
        $v = 'abc';
        $this->assertTrue($this->check('string|min:3', $v));
    }

    public function test_max_length_is_rejected_without_sanitize(): void
    {
        $v = 'abcdefgh';
        $this->assertFalse($this->check('string|max:5', $v));
    }

    public function test_max_length_truncates_with_sanitize(): void
    {
        $v = 'abcdefgh';
        $this->assertTrue($this->check('string|sanitize:trim|max:5', $v));
        $this->assertSame('abcde', $v);
    }

    public function test_exact_length(): void
    {
        $v = 'abcde';
        $this->assertTrue($this->check('string|length:5', $v));
        $v = 'abcd';
        $this->assertFalse($this->check('string|length:5', $v));
    }

    public static function formats(): array
    {
        return [
            ['email', 'john@example.com', true],
            ['email', 'not-an-email', false],
            ['uuid', '550e8400-e29b-41d4-a716-446655440000', true],
            ['uuid', '550e8400-e29b-41d4-a716-44665544000', false],
            ['ip', '192.168.1.1', true],
            ['ip', '999.999.1.1', false],
            ['slug', 'mon-slug-42', true],
            ['slug', 'Mon Slug', false],
            ['alnum', 'abc123', true],
            ['alnum', 'abc 123', false],
            ['url', 'https://example.com', true],
            ['url', 'not an url', false],
        ];
    }

    #[DataProvider('formats')]
    public function test_format_option(string $format, string $value, bool $expected): void
    {
        $v = $value;
        $this->assertSame($expected, $this->check("string|format:{$format}", $v));
    }

    public function test_duplicate_option_last_one_wins(): void
    {
        $v = '  ABC  ';
        $this->assertTrue($this->check('string|sanitize:upper|sanitize:trim', $v));
        $this->assertSame('ABC', $v);
    }

    public function test_sanitize_trim_writes_back(): void
    {
        $v = '  abc  ';
        $this->assertTrue($this->check('string|sanitize:trim', $v));
        $this->assertSame('abc', $v);
    }

    public function test_sanitize_upper_writes_back(): void
    {
        $v = 'abc';
        $this->assertTrue($this->check('string|sanitize:upper', $v));
        $this->assertSame('ABC', $v);
    }

    public function test_no_space_rejects_spaces(): void
    {
        $v = 'a b';
        $this->assertFalse($this->check('string|noSpace:true', $v));
    }

    public function test_no_special_rejects_specials(): void
    {
        $v = 'ab!';
        $this->assertFalse($this->check('string|noSpecial:true', $v));
        $v = 'ab12';
        $this->assertTrue($this->check('string|noSpecial:true', $v));
    }

    public function test_pattern_option(): void
    {
        $v = 'ABC123';
        $this->assertTrue($this->check('string|pattern:/^[A-Z0-9]+$/', $v));
        $v = 'abc';
        $this->assertFalse($this->check('string|pattern:/^[A-Z0-9]+$/', $v));
    }

    public function test_invalid_pattern_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(StringValidator::class, 'string|pattern:/[unclosed');
    }

    public function test_unknown_format_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(StringValidator::class, 'string|format:semver');
    }

    public function test_custom_message(): void
    {
        $v = 'ab';
        $this->check('string|min:3|message: trop court', $v);
        $this->assertStringContainsString('trop court', Validator::errors('field')[0]);
    }
}
