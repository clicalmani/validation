<?php
namespace Validation\Test;

use Clicalmani\Validation\Rule;
use Clicalmani\Validation\Validator;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * TestCase class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Validator::setErrorLevel(Validator::ERROR_SILENCE);
        $this->resetValidatorState();
    }

    /** L'état statique de Validator fuit entre les tests : on le remet à zéro. */
    protected function resetValidatorState(): void
    {
        foreach (['errors', 'passed'] as $prop) {
            $r = new \ReflectionProperty(Validator::class, $prop);
            $r->setValue(null, []);
        }
    }

    /** Instancie une règle depuis un pattern complet ('boolean|strict:true') et résout ses options. */
    protected function rule(string $class, string $pattern, string $param = 'field'): Rule
    {
        $rule = new $class(parameter: $param, pattern: $pattern);
        $rule->checkOptions();
        return $rule;
    }

    /** Les tests d'intégration ont besoin des validateurs enregistrés par le provider. */
    protected function requireValidators(): void
    {
        if (!\Clicalmani\Core\Providers\ValidationServiceProvider::seemsValidator('int')) {
            $this->markTestSkipped('ValidationServiceProvider non initialisé.');
        }
    }
}