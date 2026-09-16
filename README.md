Pour ce package PHP, l'outil standard est **PHPUnit**. Voici un guide complet et concret.

---

## 1. Installation

```bash
composer require --dev phpunit/phpunit
```

Dans `composer.json`, ajouter l'autoload de test :

```json
{
    "autoload-dev": {
        "psr-4": {
            "Clicalmani\\Validation\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "vendor/bin/phpunit"
    }
}
```

```bash
composer dump-autoload
```

---

## 2. Configuration `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">

    <testsuites>
        <testsuite name="Validation">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

---

## 3. Structure des tests

```
tests/
├── Unit/
│   ├── ValidatorTest.php
│   ├── RuleTest.php
│   └── RuleOptionTest.php
├── Integration/
│   └── ValidationFlowTest.php
└── Fixtures/
    └── Rules/
        ├── EmailRule.php     ← règle de test
        └── IntegerRule.php
```

---

## 4. Fixtures — règles de test

```php
// tests/Fixtures/Rules/EmailRule.php
namespace Clicalmani\Validation\Tests\Fixtures\Rules;

use Clicalmani\Validation\Rule;

class EmailRule extends Rule
{
    protected static string $argument = 'email';

    public function validate(mixed &$value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function options(): array
    {
        return [
            'max' => ['required' => false, 'type' => 'integer'],
        ];
    }
}
```

---

## 5. Les tests unitaires

```php
// tests/Unit/ValidatorTest.php
namespace Clicalmani\Validation\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Clicalmani\Validation\Validator;
use Clicalmani\Validation\Exceptions\ValidationException;

class ValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        // Enregistre les règles de test avant chaque test
        ValidationServiceProvider::register('email', EmailRule::class);
        ValidationServiceProvider::register('integer', IntegerRule::class);
    }

    // -------------------------------------------------------
    // sanitize()
    // -------------------------------------------------------

    /** @test */
    public function it_passes_a_valid_email(): void
    {
        $inputs = ['email' => 'alice@example.com'];

        $validator = new Validator();
        $result = $validator->sanitize($inputs, ['email' => 'required|email']);

        $this->assertTrue($result);
        $this->assertSame('alice@example.com', $inputs['email']);
    }

    /** @test */
    public function it_throws_on_invalid_email_in_throw_mode(): void
    {
        $this->expectException(ValidationException::class);

        $inputs = ['email' => 'not-an-email'];
        $validator = new Validator(Validator::ERROR_THROW);
        $validator->sanitize($inputs, ['email' => 'required|email']);
    }

    /** @test */
    public function it_collects_errors_in_silence_mode(): void
    {
        $inputs = ['email' => 'not-an-email'];
        $validator = new Validator(Validator::ERROR_SILENCE);
        $validator->sanitize($inputs, ['email' => 'required|email']);

        $this->assertTrue(Validator::hasErrors());
        $this->assertNotEmpty(Validator::errors('email'));
    }

    /** @test */
    public function it_sets_nullable_field_to_null_when_empty(): void
    {
        $inputs = ['name' => '   '];
        $validator = new Validator();
        $validator->sanitize($inputs, ['name' => 'nullable|string']);

        $this->assertNull($inputs['name']);
    }

    /** @test */
    public function it_fails_when_required_field_is_missing(): void
    {
        $this->expectException(ValidationException::class);

        $inputs = [];
        $validator = new Validator(Validator::ERROR_THROW);
        $validator->sanitize($inputs, ['email' => 'required|email']);
    }

    /** @test */
    public function it_sets_sometimes_field_to_null_when_absent(): void
    {
        $inputs = [];
        $validator = new Validator();
        $validator->sanitize($inputs, ['phone' => 'sometimes|string']);

        $this->assertNull($inputs['phone']);
    }

    // -------------------------------------------------------
    // confirmed
    // -------------------------------------------------------

    /** @test */
    public function it_passes_confirmed_field_when_matching(): void
    {
        $inputs = [
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ];
        $validator = new Validator();
        $result = $validator->sanitize($inputs, ['password' => 'required|confirmed|string']);

        $this->assertTrue($result);
        $this->assertArrayNotHasKey('password_confirmation', $inputs);
    }

    /** @test */
    public function it_fails_confirmed_field_when_not_matching(): void
    {
        $this->expectException(ValidationException::class);

        $inputs = [
            'password'              => 'secret123',
            'password_confirmation' => 'different',
        ];
        $validator = new Validator(Validator::ERROR_THROW);
        $validator->sanitize($inputs, ['password' => 'required|confirmed|string']);
    }

    // -------------------------------------------------------
    // errors()
    // -------------------------------------------------------

    /** @test */
    public function errors_are_reset_between_make_calls(): void
    {
        $inputs = ['email' => 'bad'];

        Validator::make(['email' => 'required|email'], $inputs, Validator::ERROR_SILENCE);
        $this->assertTrue(Validator::hasErrors());

        // Second appel — les erreurs doivent être vidées
        Validator::make(['email' => 'required|email'], ['email' => 'ok@ok.com'], Validator::ERROR_SILENCE);
        $this->assertFalse(Validator::hasErrors());
    }

    /** @test */
    public function errors_returns_specific_parameter_errors(): void
    {
        $inputs = ['email' => 'bad', 'age' => 'not-an-int'];
        Validator::make(
            ['email' => 'required|email', 'age' => 'required|integer'],
            $inputs,
            Validator::ERROR_SILENCE
        );

        $emailErrors = Validator::errors('email');
        $this->assertIsArray($emailErrors);
        $this->assertNotEmpty($emailErrors);

        // age ne doit pas contaminer email
        foreach ($emailErrors as $msg) {
            $this->assertStringNotContainsStringIgnoringCase('age', $msg);
        }
    }

    // -------------------------------------------------------
    // getArguments() / getArgument()
    // -------------------------------------------------------

    /** @test */
    public function it_extracts_arguments_from_pattern(): void
    {
        $args = Validator::getArguments('required|email|max:255');
        $this->assertTrue($args->contains('required'));
        $this->assertTrue($args->contains('email'));
    }

    /** @test */
    public function it_returns_null_for_unknown_argument(): void
    {
        $rule = (new Validator())->getRule('required|unknown_xyz', 'field');
        $this->assertNull($rule);
    }

    /** @test */
    public function it_parses_argument_options_correctly(): void
    {
        $options = Validator::getArgumentOptions('required|email|max:255', 'email');
        $this->assertArrayHasKey('max', $options);
        $this->assertSame('255', $options['max']);
    }
}
```

---

## 6. Test de RuleOption — cas limites

```php
// tests/Unit/RuleOptionTest.php

class RuleOptionTest extends TestCase
{
    /** @test */
    public function it_throws_when_required_option_is_empty(): void
    {
        $this->expectException(ValidationException::class);

        $value = '';
        $option = new RuleOption('min', $value, is_required: true);
        $option->validate();
    }

    /** @test */
    public function it_casts_value_to_correct_type(): void
    {
        $value = '42';
        $option = new RuleOption('min', $value, type: 'integer');
        $option->validate();

        $this->assertSame(42, $value); // modifié par référence
    }

    /** @test */
    public function it_maps_indexed_array_to_named_keys(): void
    {
        $value = ['Alice', 25];
        $option = new RuleOption('fields', $value, keys: ['name', 'age']);
        $option->validate();

        $this->assertSame(['name' => 'Alice', 'age' => 25], $value);
    }

    /** @test */
    public function it_applies_transform_function(): void
    {
        $value = '  hello  ';
        $option = new RuleOption(
            'trim',
            $value,
            func: fn($v) => trim($v)
        );
        $option->validate();

        $this->assertSame('hello', $value);
    }
}
```

---

## 7. Lancer les tests

```bash
# Tous les tests
composer test

# Avec couverture de code (nécessite Xdebug ou PCOV)
vendor/bin/phpunit --coverage-html coverage/

# Un seul fichier
vendor/bin/phpunit tests/Unit/ValidatorTest.php

# Un seul test par nom
vendor/bin/phpunit --filter it_throws_on_invalid_email_in_throw_mode
```

---

## Priorités pour commencer

Les tests les plus rentables à écrire en premier, dans l'ordre :

1. **`sanitize()` + `required`/`nullable`/`sometimes`** — c'est le chemin critique
2. **`confirmed`** — le bug potentiel le plus impactant côté utilisateur
3. **`errors()` + reset entre appels** — valide le nouveau code ajouté
4. **`RuleOption::validate()`** — beaucoup de branches, facile à tester isolément