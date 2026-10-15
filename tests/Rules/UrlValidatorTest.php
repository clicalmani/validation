<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\UrlValidator;
use Validation\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * UrlValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class UrlValidatorTest extends TestCase
{
    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(UrlValidator::class, $pattern)->validate($value);
    }

    public function test_valid_url(): void
    {
        $v = 'https://example.com/path?q=1';
        $this->assertTrue($this->check('url', $v));
        $this->assertSame('https://example.com/path?q=1', $v);
    }

    public static function invalidUrls(): array
    {
        return [['not an url'], [''], [null], ['https://']];
    }

    #[DataProvider('invalidUrls')]
    public function test_invalid_urls(mixed $in): void
    {
        $v = $in;
        $this->assertFalse($this->check('url', $v));
    }

    public function test_scheme_is_prepended_when_missing(): void
    {
        $v = 'example.com';
        $this->assertTrue($this->check('url', $v));
        $this->assertSame('https://example.com', $v);
    }

    public function test_schemes_restriction(): void
    {
        $v = 'ftp://example.com/file.txt';
        $this->assertFalse($this->check('url', $v));

        $v = 'ftp://example.com/file.txt';
        $this->assertTrue($this->check('url|schemes:http,https,ftp', $v));
    }

    public function test_allowed_domains_includes_subdomains(): void
    {
        $v = 'https://api.example.com';
        $this->assertTrue($this->check('url|allowedDomains:example.com', $v));

        $v = 'https://evil.com';
        $this->assertFalse($this->check('url|allowedDomains:example.com', $v));
    }

    public function test_blocked_domains(): void
    {
        $v = 'https://spam.example.com';
        $this->assertFalse($this->check('url|blockedDomains:spam.example.com', $v));

        $v = 'https://example.com';
        $this->assertTrue($this->check('url|blockedDomains:spam.com', $v));
    }

    public function test_non_standard_port_is_rejected(): void
    {
        $v = 'http://example.com:8080';
        $this->assertFalse($this->check('url', $v));
    }

    public function test_explicitly_allowed_port(): void
    {
        $v = 'http://example.com:8080';
        $this->assertTrue($this->check('url|allowedPorts:8080', $v));
    }

    public function test_standard_port_is_accepted(): void
    {
        $v = 'https://example.com:443';
        $this->assertTrue($this->check('url', $v));
    }

    public function test_url_is_lowercased_on_output(): void
    {
        $v = 'HTTPS://EXAMPLE.COM/Path';
        $this->assertTrue($this->check('url', $v));
        $this->assertSame('https://example.com/Path', $v);
    }

    public function test_parsed_url_components(): void
    {
        /** @var \Clicalmani\Validation\Rules\UrlValidator */
        $rule = $this->rule(UrlValidator::class, 'url');
        $v = 'https://example.com:443/a?b=1#top';
        $this->assertTrue($rule->validate($v));

        $parsed = $rule->getParsedUrl();
        $this->assertSame('example.com', $parsed['host']);
        $this->assertSame('/a', $parsed['path']);
        $this->assertSame('b=1', $parsed['query']);
        $this->assertSame('top', $parsed['fragment']);
    }
}