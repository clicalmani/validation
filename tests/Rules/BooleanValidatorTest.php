<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Validation\Rules\BooleanValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * BooleanValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class BooleanValidatorTest extends TestCase
{
    public static function truthy(): array { return [[true], ['true'], ['YES'], ['on'], [1], ['1'], ['oui']]; }
    public static function falsy(): array  { return [[false], ['false'], ['No'], ['off'], [0], ['0'], ['non']]; }
    public static function invalid(): array { return [['maybe'], ['truee'], [[]]]; }

    #[DataProvider('truthy')]
    public function test_truthy(mixed $in): void
    {
        $v = $in;
        $this->assertTrue($this->rule(BooleanValidator::class, 'boolean')->validate($v));
        $this->assertTrue($v);
    }

    #[DataProvider('falsy')]
    public function test_falsy(mixed $in): void
    {
        $v = $in;
        $this->assertTrue($this->rule(BooleanValidator::class, 'boolean')->validate($v));
        $this->assertFalse($v);
    }

    #[DataProvider('invalid')]
    public function test_invalid(mixed $in): void
    {
        $v = $in;
        $this->assertFalse($this->rule(BooleanValidator::class, 'boolean')->validate($v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_strict_with_value(): void
    {
        $v = 'yes';
        $this->assertFalse($this->rule(BooleanValidator::class, 'boolean|strict:true')->validate($v));
    }

    public function test_strict_as_flag(): void
    {
        $v = 'yes';
        $this->assertFalse($this->rule(BooleanValidator::class, 'boolean|strict')->validate($v)); // ROUGE : isset(null)
    }

    public function test_strings_false_rejects_string_input(): void
    {
        $v = 'yes';
        $this->assertFalse($this->rule(BooleanValidator::class, 'boolean|strings:false')->validate($v)); // ROUGE : option inutilisée
    }

    public function test_default_applies_on_empty(): void
    {
        $v = '';
        $this->assertTrue($this->rule(BooleanValidator::class, 'boolean|default:true')->validate($v));
        $this->assertTrue($v);
    }

    public function test_validate_without_check_options_does_not_crash(): void
    {
        $v = '';
        $rule = new BooleanValidator('f', 'boolean');
        $this->assertTrue($rule->validate($v)); // ROUGE : $options typé non initialisé
    }

    public function test_unknown_option_throws(): void
    {
        $this->expectException(ValidationException::class);
        $this->rule(BooleanValidator::class, 'boolean|foo:bar');
    }

    public function test_custom_message(): void
    {
        $v = 'maybe';
        $this->rule(BooleanValidator::class, 'boolean|message:Champ invalide')->validate($v);
        $this->assertStringContainsString('Champ invalide', Validator::errors('field')[0]);
    }
}