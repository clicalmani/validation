<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\EmailValidator;
use Validation\Test\TestCase;
/**
 * EmailValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class EmailValidatorTest extends TestCase
{
    private function check(string $pattern, mixed $value): bool
    {
        return $this->rule(EmailValidator::class, $pattern)->validate($value);
    }

    public function test_valid_and_invalid_formats(): void
    {
        $this->assertTrue($this->check('email', 'john@example.com'));
        $this->assertFalse($this->check('email', 'john@'));
        $this->assertFalse($this->check('email', ''));
    }

    public function test_blocked_domain(): void
    {
        $this->assertFalse($this->check('email|block:spam.com,junk.org', 'a@spam.com'));
        $this->assertTrue($this->check('email|block:spam.com', 'a@example.com'));
    }

    public function test_blocked_domain_is_case_insensitive(): void
    {
        $this->assertFalse($this->check('email|block:spam.com', 'a@SPAM.com')); // ROUGE
    }

    public function test_only_domains(): void
    {
        $this->assertTrue($this->check('email|only:corp.com,partner.com', 'a@corp.com'));
        $this->assertFalse($this->check('email|only:corp.com,partner.com', 'a@other.com'));
    }

    public function test_surrounding_whitespace_is_trimmed(): void
    {
        $v = '  john@example.com ';
        $this->assertTrue($this->rule(EmailValidator::class, 'email')->validate($v)); // ROUGE
        $this->assertSame('john@example.com', $v);
    }
}