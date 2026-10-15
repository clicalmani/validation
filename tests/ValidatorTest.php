<?php
namespace Validation\Test;

use Clicalmani\Validation\Validator;

/**
 * ValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class ValidatorTest extends TestCase
{
    // ---------- Parsing de patterns (pur, sans provider) ----------

    public function test_get_arguments_ignores_options_with_values(): void
    {
        $args = Validator::getArguments('required|int|min:3');
        $this->assertTrue($args->contains('int'));
        $this->assertFalse($args->contains('min:3'));
    }

    public function test_get_argument_skips_default_arguments(): void
    {
        $this->assertSame('int', Validator::getArgument('required|nullable|int|min:3'));
        $this->assertNull(Validator::getArgument('required|nullable'));
    }

    public function test_get_argument_options(): void
    {
        $this->assertSame(['min' => '3', 'max' => '10'], Validator::getArgumentOptions('required|int|min:3|max:10'));
        $this->assertSame([], Validator::getArgumentOptions('required|nullable'));
    }

    public function test_option_value_may_contain_colons(): void
    {
        $this->assertSame(['format' => 'Y-m-d H:i:s'], Validator::getArgumentOptions('datetime|format:Y-m-d H:i:s'));
    }

    public function test_flag_option_without_value(): void
    {
        // Comportement actuel : ['strict' => null]. À décider : null => true ?
        $this->assertArrayHasKey('strict', Validator::getArgumentOptions('boolean|strict'));
    }

    // ---------- Intégration (providers requis) ----------

    public function test_errors_are_reset_between_make_calls(): void
    {
        $this->requireValidators();

        Validator::make(['a' => 'int'], ['a' => 'x']);
        $this->assertTrue(Validator::hasErrors());

        Validator::make(['a' => 'int'], ['a' => '1']);
        $this->assertFalse(Validator::hasErrors()); // ROUGE : $errors n'est jamais vidé
    }

    public function test_required_missing_parameter_is_reported(): void
    {
        $this->requireValidators();

        Validator::make(['name' => 'required|string'], ['other' => 1]);
        $this->assertNotEmpty(Validator::errors('name'));
    }

    public function test_sometimes_missing_parameter_is_ignored(): void
    {
        $this->requireValidators();

        $v = Validator::make(['name' => 'sometimes|string'], ['other' => 1]);
        $this->assertFalse(Validator::hasErrors());
        $this->assertArrayNotHasKey('name', $v->validated());
    }

    public function test_nullable_empty_string_becomes_null(): void
    {
        $this->requireValidators();

        $v = Validator::make(['age' => 'nullable|int'], ['age' => '  ']);
        $this->assertNull($v->validated()['age']);
    }

    public function test_nullable_non_empty_value_is_still_validated(): void
    {
        $this->requireValidators();

        Validator::make(['age' => 'nullable|int'], ['age' => 'abc']);
        $this->assertNotEmpty(Validator::errors('age')); // ROUGE : le `continue` saute la validation
    }

    public function test_hash_is_applied_after_validation(): void
    {
        $this->requireValidators();

        Validator::make(['pwd' => 'password|hash|min:8'], ['pwd' => 'short']);
        $this->assertNotEmpty(Validator::errors('pwd')); // ROUGE : hash + continue court-circuite la règle
    }

    public function test_confirmed_mismatch_is_reported(): void
    {
        $this->requireValidators();

        Validator::make(
            ['pwd' => 'required|string|confirmed'],
            ['pwd' => 'abc', 'pwd_confirmation' => 'xyz']
        );
        $this->assertNotEmpty(Validator::errors('pwd_confirmation'));
    }

    public function test_confirmed_match_removes_confirmation_field(): void
    {
        $this->requireValidators();

        $v = Validator::make(
            ['pwd' => 'required|string|confirmed'],
            ['pwd' => 'abc', 'pwd_confirmation' => 'abc']
        );
        $this->assertArrayNotHasKey('pwd_confirmation', $v->validated());
    }
}