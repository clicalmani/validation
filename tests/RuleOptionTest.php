<?php
namespace Validation\Test;

use Clicalmani\Validation\Exceptions\ValidationException;
use Clicalmani\Validation\RuleOption;

/**
 * RuleOptionTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class RuleOptionTest extends TestCase
{
    public function test_function_then_type(): void
    {
        $v = '1,2';
        (new RuleOption('ids', $v, null, 'array', [], fn($s) => explode(',', $s)))->validate();
        $this->assertSame(['1', '2'], $v);
    }

    public function test_validator_failure_throws(): void
    {
        $v = 'nope';
        $this->expectException(ValidationException::class);
        (new RuleOption('x', $v, null, null, [], null, fn($x) => false))->validate();
    }

    public function test_keys_mapping(): void
    {
        $v = ['a', 'b'];
        (new RuleOption('pair', $v, null, 'array', ['x', 'y']))->validate();
        $this->assertSame(['x' => 'a', 'y' => 'b'], $v);
    }

    public function test_required_option_throws_validation_exception(): void
    {
        $v = null;
        $this->expectException(ValidationException::class); // ROUGE : sprintf() à 1 argument => ArgumentCountError
        (new RuleOption('model', $v, true))->validate();
    }

    public function test_default_is_applied(): void
    {
        $v = null;
        (new RuleOption('min', $v, false, 'int', [], null, null, 5))->validate();
        $this->assertSame(5, $v); // ROUGE : $default non défini + cast avant défaut
    }
}