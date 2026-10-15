<?php
namespace Validation\Test\Rules;

use Clicalmani\Validation\Rules\PDFValidator;
use Validation\Test\TestCase;

/**
 * PDFValidatorTest class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
final class PDFValidatorTest extends TestCase
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

    private function makePdf(string $version = '1.7', bool $withEof = true, bool $withObjects = true): string
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'pdf');

        $content = "%PDF-{$version}\n";

        if ($withObjects) {
            $content .= "1 0 obj\n<< /Type /Catalog >>\nendobj\n";
        }

        $content .= "trailer\n";

        if ($withEof) {
            $content .= "%%EOF\n";
        }

        file_put_contents($this->tmpFile, $content);

        return $this->tmpFile;
    }

    private function upload(string $path): array
    {
        return [
            'name' => 'document.pdf',
            'size' => filesize($path),
            'tmp_name' => $path,
            'type' => 'application/pdf',
            'error' => UPLOAD_ERR_OK,
        ];
    }

    private function check(string $pattern, mixed &$value): bool
    {
        return $this->rule(PDFValidator::class, $pattern)->validate($value);
    }

    public function test_valid_pdf_passes(): void
    {
        $path = $this->makePdf();
        $v = $this->upload($path);
        $this->assertTrue($this->check('pdf', $v));
        $this->assertSame('document.pdf', $v['name']);
    }

    public function test_missing_pdf_signature_is_rejected(): void
    {
        $path = $this->makePdf();
        file_put_contents($path, "NOT A PDF\n%%EOF\n");
        $v = $this->upload($path);
        $this->assertFalse($this->check('pdf', $v));
    }

    public function test_missing_eof_marker_is_rejected(): void
    {
        $path = $this->makePdf(withEof: false);
        $v = $this->upload($path);
        $this->assertFalse($this->check('pdf', $v));
    }

    public function test_content_without_pdf_objects_is_rejected(): void
    {
        $path = $this->makePdf(withObjects: false);
        $v = $this->upload($path);
        $this->assertFalse($this->check('pdf', $v));
    }

    public function test_content_check_can_be_disabled(): void
    {
        $path = $this->makePdf(withObjects: false);
        $v = $this->upload($path);
        $this->assertTrue($this->check('pdf|checkContent:false', $v));
    }

    public function test_min_version(): void
    {
        $path = $this->makePdf(version: '1.4');
        $v = $this->upload($path);
        $this->assertFalse($this->check('pdf|minVersion:1.5', $v));

        $v = $this->upload($path);
        $this->assertTrue($this->check('pdf|minVersion:1.4', $v));
    }

    public function test_max_version(): void
    {
        $path = $this->makePdf(version: '1.8');
        $v = $this->upload($path);
        $this->assertFalse($this->check('pdf|maxVersion:1.7', $v));
    }

    public function test_invalid_version_option_throws(): void
    {
        $this->expectException(\Clicalmani\Validation\Exceptions\ValidationException::class);
        $this->rule(PDFValidator::class, 'pdf|minVersion:abc');
    }

    public function test_non_pdf_extension_is_rejected_by_default(): void
    {
        $path = $this->makePdf();
        $v = $this->upload($path);
        $v['name'] = 'document.txt';
        $this->assertFalse($this->check('pdf', $v));
    }
}