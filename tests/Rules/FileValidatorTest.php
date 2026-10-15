<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\FileValidator;
use Validation\Test\TestCase;
use Clicalmani\Validation\Validator;

/**
 * FileValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class FileValidatorTest extends TestCase
{
    private function file(array $overrides = []): array
    {
        return array_merge([
            'name' => 'document.pdf',
            'size' => 2048,
            'tmp_name' => '/tmp/phpABCD',
            'type' => 'application/pdf',
            'error' => UPLOAD_ERR_OK,
        ], $overrides);
    }

    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(FileValidator::class, $pattern)->validate($value);
    }

    public function test_valid_file_passes_and_is_normalized(): void
    {
        $v = $this->file();
        $this->assertTrue($this->check('file', $v));
        $this->assertSame('document.pdf', $v['name']);
    }

    public function test_missing_file_is_rejected(): void
    {
        $v = null;
        $this->assertFalse($this->check('file', $v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_no_upload_error_is_rejected(): void
    {
        $v = $this->file(['error' => UPLOAD_ERR_NO_FILE]);
        $this->assertFalse($this->check('file', $v));
    }

    public function test_allow_empty_accepts_missing_file(): void
    {
        $v = $this->file(['error' => UPLOAD_ERR_NO_FILE]);
        $this->assertTrue($this->check('file|allowEmpty:true', $v));
    }

    public function test_upload_error_is_reported(): void
    {
        $v = $this->file(['error' => UPLOAD_ERR_INI_SIZE]);
        $this->assertFalse($this->check('file', $v));
        $this->assertStringContainsString('maximum size', Validator::errors('field')[0]);
    }

    public function test_max_size_with_unit_suffix(): void
    {
        $v = $this->file(['size' => 2048]);
        $this->assertTrue($this->check('file|max:1M', $v));

        $v = $this->file(['size' => 3 * 1024 * 1024]);
        $this->assertFalse($this->check('file|max:1M', $v));
    }

    public function test_min_size(): void
    {
        $v = $this->file(['size' => 10]);
        $this->assertFalse($this->check('file|min:1K', $v));
    }

    public function test_extension_whitelist(): void
    {
        $v = $this->file(['name' => 'photo.JPG']);
        $this->assertTrue($this->check('file|ext:jpg,png', $v));

        $v = $this->file(['name' => 'virus.exe']);
        $this->assertFalse($this->check('file|ext:jpg,png', $v));
    }

    public function test_mime_whitelist(): void
    {
        $v = $this->file(['type' => 'application/pdf']);
        $this->assertTrue($this->check('file|mime:application/pdf', $v));

        $v = $this->file(['type' => 'application/zip']);
        $this->assertFalse($this->check('file|mime:application/pdf', $v));
    }

    public function test_filename_pattern(): void
    {
        $v = $this->file(['name' => '2026-10-15_report.pdf']);
        $this->assertTrue($this->check('file|filename:/^\d{4}-\d{2}-\d{2}_/', $v));

        $v = $this->file(['name' => 'report.pdf']);
        $this->assertFalse($this->check('file|filename:/^\d{4}-\d{2}-\d{2}_/', $v));
    }

    public function test_unknown_option_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(FileValidator::class, 'file|foo:bar');
    }
}