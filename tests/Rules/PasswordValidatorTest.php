<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\PasswordValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;

/**
 * PasswordValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class PasswordValidatorTest extends TestCase
{
    private const GOOD = 'Xk9mQz4wLp';

    private function check(string $pattern, mixed $value): bool
    {
        $rule = $this->rule(PasswordValidator::class, $pattern);
        return $rule->validate($value);
    }

    public function test_accepts_a_good_password_unchanged(): void
    {
        $v = self::GOOD;
        $this->assertTrue($this->rule(PasswordValidator::class, 'password')->validate($v));
        $this->assertSame(self::GOOD, $v);
    }

    public function test_too_short(): void
    {
        $this->assertFalse($this->check('password', 'Ab1'));
        $this->assertStringContainsString('at least 8', Validator::errors('field')[0]);
    }

    public function test_too_long(): void
    {
        $this->assertFalse($this->check('password|max:10', self::GOOD . 'A'));
    }

    public function test_requires_uppercase(): void
    {
        $this->assertFalse($this->check('password', 'abcdefg1'));
        $this->assertStringContainsString('uppercase', Validator::errors('field')[0]);
    }

    public function test_exact_length(): void
    {
        $this->assertTrue($this->check('password|length:10', self::GOOD));
        $this->assertFalse($this->check('password|length:10', 'Xk9mQz4w'));
    }

    public function test_symbols_required(): void
    {
        $this->assertFalse($this->check('password|symbols:1', self::GOOD));
        $this->assertTrue($this->check('password|symbols:1', 'Xk9mQz4wL!'));
    }

    public function test_spaces_rejected_by_default(): void
    {
        $this->assertFalse($this->check('password', 'Xk9m Qz4wLp')); // ROUGE : défaut noSpace jamais appliqué
    }

    public function test_repeat_limit(): void
    {
        $this->assertFalse($this->check('password|repeat:2', 'Xk9mQQQz4w'));
        $this->assertTrue($this->check('password|repeat:2', 'Xk9mQQz4wL'));
    }

    public function test_common_password_rejected_by_default(): void
    {
        $this->assertFalse($this->check('password', 'Password123')); // ROUGE : logique inversée
    }

    public function test_common_false_disables_the_check(): void
    {
        $this->assertTrue($this->check('password|common:false', 'Password123')); // ROUGE
    }

    public function test_strength_scores(): void
    {
        $rule = $this->rule(PasswordValidator::class, 'password');
        $this->assertSame('weak', $rule->getStrength('abc'));
        $this->assertSame('very_strong', $rule->getStrength('Xk9mQz4wLp!x1Zq9'));
    }

    public function test_non_string_input_is_a_validation_failure_not_an_exception(): void
    {
        $this->assertFalse($this->check('password', [])); // ROUGE : parseString lève InvalidArgumentException
    }
}