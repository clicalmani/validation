<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Validation\Rules\InValidator;
use Clicalmani\Validation\Rules\NotInValidator;
use Clicalmani\Validation\Rules\EnumValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * EnumValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class EnumValidatorTest extends TestCase
{
    private function check(string $class, string $pattern, mixed &$value): bool
    {
        return $this->rule($class, $pattern)->validate($value);
    }

    public function test_list_option_is_required(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(EnumValidator::class, 'enum');
    }

    public function test_membership_in_comma_separated_list(): void
    {
        $v = 'b';
        $this->assertTrue($this->check(EnumValidator::class, 'enum|list:a,b,c', $v));
    }

    public function test_non_member_is_rejected(): void
    {
        $v = 'z';
        $this->assertFalse($this->check(EnumValidator::class, 'enum|list:a,b,c', $v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_numeric_values_are_typed(): void
    {
        $v = 2;
        $this->assertTrue($this->check(EnumValidator::class, 'enum|list:1,2,3', $v));
        $v = '2';
        $this->assertFalse($this->check(EnumValidator::class, 'enum|list:1,2,3', $v));
    }

    public function test_loose_comparison_accepts_numeric_strings(): void
    {
        $v = '2';
        $this->assertTrue($this->check(EnumValidator::class, 'enum|list:1,2,3|strict:false', $v));
    }

    public function test_case_insensitive(): void
    {
        $v = 'RED';
        $this->assertTrue($this->check(EnumValidator::class, 'enum|list:red,green|caseInsensitive:true', $v));
        $v = 'RED';
        $this->assertFalse($this->check(EnumValidator::class, 'enum|list:red,green', $v));
    }

    public function test_sanitize_option(): void
    {
        $v = '  red  ';
        $this->assertTrue($this->check(EnumValidator::class, 'enum|list:red,green|sanitize:trim', $v));
    }

    public function test_json_list(): void
    {
        $v = 'one';
        $this->assertTrue($this->check(EnumValidator::class, 'enum|list:["one","two"]', $v));
    }

    public function test_in_alias(): void
    {
        $v = 'a';
        $this->assertTrue($this->check(InValidator::class, 'in|list:a,b,c', $v));
    }

    public function test_not_in_inverts_membership(): void
    {
        $v = 'z';
        $this->assertTrue($this->check(NotInValidator::class, 'not_in|list:a,b,c', $v));
        $this->assertEmpty(Validator::errors('field'));

        $v = 'a';
        $this->assertFalse($this->check(NotInValidator::class, 'not_in|list:a,b,c', $v));
    }

    public static function invalidValues(): array
    {
        return [[null], [['a']]];
    }

    #[DataProvider('invalidValues')]
    public function test_null_is_rejected(mixed $in): void
    {
        $v = $in;
        $this->assertFalse($this->check(EnumValidator::class, 'enum|list:a,b', $v));
    }

    public function test_custom_message(): void
    {
        $v = 'z';
        $this->check(EnumValidator::class, 'enum|list:a,b|message:Valeur interdite', $v);
        $this->assertStringContainsString('Valeur interdite', Validator::errors('field')[0]);
    }
}