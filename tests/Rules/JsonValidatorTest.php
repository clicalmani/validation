<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\JsonValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * JsonValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class JsonValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(JsonValidator::class, $pattern)->validate($value);
    }

    public function test_valid_json_object_is_decoded_as_array(): void
    {
        $v = '{"a":1}';
        $this->assertTrue($this->check('json', $v));
        $this->assertSame(['a' => 1], $v);
    }

    public function test_assoc_false_decodes_to_object(): void
    {
        $v = '{"a":1}';
        $this->assertTrue($this->check('json|assoc:false', $v));
        $this->assertInstanceOf(\stdClass::class, $v);
        $this->assertSame(1, $v->a);
    }

    public static function invalidPayloads(): array
    {
        return [['{oops'], [''], ['not json']];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_json_is_rejected(string $in): void
    {
        $v = $in;
        $this->assertFalse($this->check('json', $v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_max_size(): void
    {
        $v = str_repeat('a', 100);
        $this->assertFalse($this->check('json|maxSize:10', $v));
    }

    public function test_types_restriction(): void
    {
        $v = '[1,2]';
        $this->assertTrue($this->check('json|types:array', $v));

        // Avec assoc:true (défaut), un objet JSON est décodé en tableau PHP,
        // on teste donc la restriction avec une racine scalaire.
        $v = '"hello"';
        $this->assertFalse($this->check('json|types:array', $v));
    }

    public function test_unknown_type_is_rejected(): void
    {
        $v = '{"a":1}';
        $this->assertFalse($this->check('json|types:magic', $v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_schema_required_keys(): void
    {
        $v = '{"name":"John"}';
        $this->assertTrue($this->check('json|schema:{"required":["name"]}', $v));

        $v = '{"email":"john@example.com"}';
        $this->assertFalse($this->check('json|schema:{"required":["name"]}', $v));
    }

    public function test_schema_property_type(): void
    {
        $v = '{"age":"abc"}';
        $this->assertFalse($this->check('json|schema:{"properties":{"age":{"type":"integer"}}}', $v));

        $v = '{"age":30}';
        $this->assertTrue($this->check('json|schema:{"properties":{"age":{"type":"integer"}}}', $v));
    }

    public function test_schema_property_pattern(): void
    {
        $v = '{"code":"ABC123"}';
        $this->assertTrue($this->check('json|schema:{"properties":{"code":{"pattern":"/^[A-Z0-9]+$/"}}}', $v));

        $v = '{"code":"abc!"}';
        $this->assertFalse($this->check('json|schema:{"properties":{"code":{"pattern":"/^[A-Z0-9]+$/"}}}', $v));
    }

    public function test_schema_defaults_fill_missing_keys(): void
    {
        $v = '{"name":"John"}';
        $this->assertTrue($this->check('json|schema:{"defaults":{"role":"user"}}', $v));
        $this->assertSame('user', $v['role']);
    }

    public function test_clean_prunes_empty_values(): void
    {
        $v = '{"a":1,"b":null,"c":"","d":0}';
        $this->assertTrue($this->check('json|clean:true', $v));
        $this->assertSame(['a' => 1, 'd' => 0], $v);
    }
}