<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\ImageValidator;
use Clicalmani\Validation\Validator;
use Validation\Test\TestCase;

/**
 * ImageValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class ImageValidatorTest extends TestCase
{
    private ?string $tmpFile = null;

    protected function tearDown(): void
    {
        if ($this->tmpFile && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
            $this->tmpFile = null;
        }

        parent::tearDown();
    }

    private function makePng(): string
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($this->tmpFile, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return $this->tmpFile;
    }

    private function upload(string $path, string $name = 'photo.png', string $mime = 'image/png'): array
    {
        return [
            'name' => $name,
            'size' => filesize($path),
            'tmp_name' => $path,
            'type' => $mime,
            'error' => UPLOAD_ERR_OK,
        ];
    }

    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(ImageValidator::class, $pattern)->validate($value);
    }

    public function test_missing_file_is_rejected(): void
    {
        $v = null;
        $this->assertFalse($this->check('image', $v));
        $this->assertNotEmpty(Validator::errors('field'));
    }

    public function test_upload_error_is_rejected(): void
    {
        $v = ['name' => '', 'type' => '', 'size' => 0, 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE];
        $this->assertFalse($this->check('image', $v));
    }

    public function test_allow_empty_passes_without_file(): void
    {
        $v = null;
        $this->assertTrue($this->check('image|allowEmpty:true', $v));
    }

    public function test_valid_png_reaches_request_context_in_cli(): void
    {
        $v = $this->upload($this->makePng());
        $this->assertFalse($this->check('image', $v));
        $this->assertStringContainsString(
            'Unable to resolve current request context',
            Validator::errors('field')[0]
        );
    }
}