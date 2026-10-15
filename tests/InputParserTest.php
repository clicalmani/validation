<?php
namespace Validation\Test;

use Clicalmani\Validation\InputParser;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * InputParserTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class InputParserTest extends TestCase
{
    private InputParser $p;

    protected function setUp(): void
    {
        parent::setUp();
        $this->p = new InputParser();
    }

    public function test_parse_int(): void
    {
        $this->assertSame(12, $this->p->parseInt('12'));
        $this->assertSame(3, $this->p->parseInt(3.9));
    }

    public function test_parse_float(): void
    {
        $this->assertSame(1.5, $this->p->parseFloat('1.5'));
        $this->expectException(\InvalidArgumentException::class);
        $this->p->parseFloat('abc');
    }

    public static function booleans(): array
    {
        return [[true, true], [false, false], [1, true], [0, false], ['1', true], ['0', false]];
    }

    #[DataProvider('booleans')]
    public function test_parse_boolean(mixed $in, bool $out): void
    {
        $this->assertSame($out, $this->p->parseBoolean($in));
    }

    public function test_parse_boolean_null_returns_false(): void
    {
        $this->assertFalse($this->p->parseBoolean(null)); // ROUGE : le throw précède le test is_null
    }

    public function test_parse_boolean_rejects_garbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->p->parseBoolean('abc');
    }

    public function test_parse_string(): void
    {
        $this->assertSame('', $this->p->parseString(null));
        $this->assertSame('1', $this->p->parseString(true));
        $this->expectException(\InvalidArgumentException::class);
        $this->p->parseString([1]);
    }

    public function test_parse_array(): void
    {
        $this->assertSame([1, 2], $this->p->parseArray('[1,2]'));
        $this->assertSame([], $this->p->parseArray('{oops'));
        $this->assertSame([], $this->p->parseArray(null));
        $this->assertSame([5], $this->p->parseArray(5));
        $this->assertSame(['a' => 1], $this->p->parseArray((object) ['a' => 1]));
    }

    public function test_parse_object(): void
    {
        $this->assertSame(1, $this->p->parseObject('{"a":1}')->a);
        $this->assertEquals(new \stdClass(), $this->p->parseObject(null));
        $this->assertSame(2, $this->p->parseObject(['b' => 2])->b);
    }

    public function test_cast_aliases_and_default(): void
    {
        $this->assertSame(7, $this->p->cast('7', 'integer'));
        $this->assertTrue($this->p->cast('1', 'boolean'));
        $this->assertSame('x', $this->p->cast('x', 'unknown_type'));
    }
}