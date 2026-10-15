<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\AlphaValidator;
use Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * AlphaValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class AlphaValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(AlphaValidator::class, $pattern)->validate($value);
    }

    public static function validPlain(): array
    {
        return [['hello'], ['HelloWorld']];
    }

    #[DataProvider('validPlain')]
    public function test_letters_only_by_default(string $in): void
    {
        $v = $in;
        $this->assertTrue($this->check('alpha', $v));
    }

    public function test_empty_string_is_rejected(): void
    {
        $v = '';
        $this->assertFalse($this->check('alpha', $v));
    }

    public static function invalidPlain(): array
    {
        return [['hello world'], ['abc-123'], ['café'], ['a_b'], ["l'été"]];
    }

    #[DataProvider('invalidPlain')]
    public function test_non_letters_rejected_by_default(string $in): void
    {
        $v = $in;
        $this->assertFalse($this->check('alpha', $v));
    }

    public function test_allow_space(): void
    {
        $v = 'Jean Dupont';
        $this->assertTrue($this->check('alpha|allowSpace:true', $v));
    }

    public function test_allow_dash(): void
    {
        $v = 'Jean-Pierre';
        $this->assertTrue($this->check('alpha|allowDash:true', $v));
    }

    public function test_allow_apostrophe(): void
    {
        $v = "l'eau";
        $this->assertTrue($this->check('alpha|allowApostrophe:true', $v));
    }

    public function test_allow_underscore(): void
    {
        $v = 'hello_world';
        $this->assertTrue($this->check('alpha|allowUnderscore:true', $v));
    }

    public function test_allow_accent(): void
    {
        $v = 'café';
        $this->assertTrue($this->check('alpha|allowAccent:true', $v));
        $v = 'café';
        $this->assertFalse($this->check('alpha', $v));
    }

    public function test_unicode_letters(): void
    {
        $v = '日本語';
        $this->assertTrue($this->check('alpha|unicode:true', $v));
        $v = '日本語';
        $this->assertFalse($this->check('alpha', $v));
    }

    public function test_digits_still_rejected_with_options(): void
    {
        $v = 'abc123';
        $this->assertFalse($this->check('alpha|allowSpace:true|allowDash:true', $v));
    }

    public function test_inherits_string_length_options(): void
    {
        $v = 'ab';
        $this->assertFalse($this->check('alpha|min:3', $v));
        $v = 'abc';
        $this->assertTrue($this->check('alpha|min:3', $v));
    }
}