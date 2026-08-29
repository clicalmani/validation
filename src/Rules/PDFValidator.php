<?php

namespace Clicalmani\Validation\Rules;

/**
 * Class PDFValidator
 * 
 * Validates PDF files with support for:
 * - Extension validation (.pdf)
 * - MIME type validation (application/pdf)
 * - Header signature validation (%PDF) and end-of-file EOF checks (%%EOF)
 * - Version constraints (minVersion, maxVersion)
 * - File size constraints
 * - Content integrity validation
 * - Page count constraints
 * - Detailed error reporting and message placeholding
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class PDFValidator extends FileValidator
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'pdf';
    
    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Array storing extracted PDF metadata (version, page count).
     *
     * @var array<string, mixed>
     */
    protected array $pdfInfo = [];

    /**
     * Returns the array configuration schema for supported rule options.
     *
     * @return array<string, array{
     *     required: bool,
     *     type: string,
     *     default?: mixed,
     *     function?: callable,
     *     validator?: callable
     * }>
     */
    public function options(): array
    {
        // Retrieve parent options
        $options = parent::options();
        
        // Minimum allowed PDF version constraint (e.g., '1.4')
        $options['minVersion'] = [
            'required' => false,
            'type' => 'string',
            'validator' => fn(string $value) => preg_match('/^[0-9]+\.[0-9]+$/', $value) === 1
        ];

        // Maximum allowed PDF version constraint (e.g., '1.7')
        $options['maxVersion'] = [
            'required' => false,
            'type' => 'string',
            'validator' => fn(string $value) => preg_match('/^[0-9]+\.[0-9]+$/', $value) === 1
        ];

        // Perform content integrity checks
        $options['checkContent'] = [
            'required' => false,
            'type' => 'bool',
            'default' => true,
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        // Maximum allowed pages count constraint
        $options['maxPages'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        // Set default extension configuration for PDF files
        $options['ext'] = [
            'required' => false,
            'type' => 'array',
            'default' => ['pdf'],
            'function' => fn(string $value) => array_map('strtolower', array_map('trim', explode(',', $value)))
        ];

        // Set default MIME type configuration for PDF files
        $options['mime'] = [
            'required' => false,
            'type' => 'array',
            'default' => ['application/pdf'],
            'function' => fn(string $value) => array_map('strtolower', array_map('trim', explode(',', $value)))
        ];

        return $options;
    }

    /**
     * Validates input target file against PDF-specific constraints.
     *
     * @param mixed &$value Target file array or object reference.
     * @return bool `true` if all PDF validation rules pass, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Base validation via parent FileValidator class
        $isValid = parent::validate($value);
        
        if (!$isValid) {
            return false;
        }

        // 2. Retrieve normalized valid file
        $file = $this->getValidFile();
        
        if (empty($file)) {
            $this->addError("Unable to read the PDF file.");
            return false;
        }

        // 3. MIME type validation
        if (!$this->validateMimeType($file)) {
            return false;
        }

        // 4. Header & EOF signature validation
        if (!$this->validateSignature($file)) {
            return false;
        }

        // 5. Content integrity verification
        if ($this->options['checkContent'] ?? true) {
            if (!$this->validateContent($file)) {
                return false;
            }
        }

        // 6. PDF version check
        if (!$this->validateVersion($file)) {
            return false;
        }

        // 7. Page count validation
        if (!$this->validatePages($file)) {
            return false;
        }

        // 8. Update output array with extracted PDF metadata
        $value = array_merge($file, $this->pdfInfo);

        return true;
    }

    /**
     * Validates the MIME type of the PDF file.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateMimeType(array $file): bool
    {
        $path = (string) ($file['tmp_name'] ?? '');
        
        if (!file_exists($path)) {
            $this->addError("The PDF file does not exist.");
            return false;
        }

        $mimeType = mime_content_type($path);
        
        if ($mimeType !== 'application/pdf') {
            $this->addError("The file is not a valid PDF. Detected MIME type: {$mimeType}");
            return false;
        }

        return true;
    }

    /**
     * Validates the PDF header signature (%PDF) and end of file marker (%%EOF).
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateSignature(array $file): bool
    {
        $path = (string) ($file['tmp_name'] ?? '');
        
        if (!file_exists($path) || !is_readable($path)) {
            $this->addError("Unable to read the PDF file.");
            return false;
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->addError("Unable to open the PDF file.");
            return false;
        }

        $header = fread($handle, 1024);
        fclose($handle);

        // Verify PDF header magic bytes signature
        if (!str_starts_with((string) $header, '%PDF')) {
            $this->addError("The file does not start with a valid PDF signature (%PDF).");
            return false;
        }

        // Verify End-Of-File marker (%%EOF) in footer
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->addError("Unable to open the PDF file.");
            return false;
        }

        fseek($handle, -1024, SEEK_END);
        $footer = fread($handle, 1024);
        fclose($handle);

        if (!str_contains((string) $footer, '%%EOF')) {
            $this->addError("The PDF file is incomplete or corrupted (missing %%EOF marker).");
            return false;
        }

        return true;
    }

    /**
     * Validates PDF content integrity and object structure.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateContent(array $file): bool
    {
        $path = (string) ($file['tmp_name'] ?? '');
        
        if (!file_exists($path) || !is_readable($path)) {
            $this->addError("Unable to read the PDF file.");
            return false;
        }

        // Validate structure using `pdftotext` if binary is available
        if ($this->isPdfToTextAvailable()) {
            $output = (string) shell_exec("pdftotext -q " . escapeshellarg($path) . " - 2>&1");
            
            if (str_contains($output, 'Error') || str_contains($output, 'Failed')) {
                $this->addError("The PDF file appears to be corrupted.");
                return false;
            }
        }

        // Fallback structural check: look for essential PDF objects
        $content = (string) file_get_contents($path);
        
        if (!str_contains($content, 'obj') && !str_contains($content, 'endobj')) {
            $this->addError("The PDF file does not contain valid PDF object structures.");
            return false;
        }

        return true;
    }

    /**
     * Validates minimum and maximum version constraints of the PDF document.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateVersion(array $file): bool
    {
        $path = (string) ($file['tmp_name'] ?? '');
        
        if (!file_exists($path) || !is_readable($path)) {
            return true;
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return true;
        }

        $header = (string) fread($handle, 1024);
        fclose($handle);

        // Extract PDF version tag (e.g. %PDF-1.7)
        if (preg_match('/%PDF-([0-9]+\.[0-9]+)/', $header, $matches)) {
            $version = $matches[1];
            $this->pdfInfo['version'] = $version;

            // Enforce minimum version limit
            if ($minVersion = $this->options['minVersion'] ?? null) {
                if (version_compare($version, $minVersion, '<')) {
                    $this->addError("The PDF version ({$version}) is lower than the required minimum version ({$minVersion}).");
                    return false;
                }
            }

            // Enforce maximum version limit
            if ($maxVersion = $this->options['maxVersion'] ?? null) {
                if (version_compare($version, $maxVersion, '>')) {
                    $this->addError("The PDF version ({$version}) exceeds the maximum allowed version ({$maxVersion}).");
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates total page count against maximum allowed page constraints.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validatePages(array $file): bool
    {
        $maxPages = $this->options['maxPages'] ?? null;

        if (!$maxPages) {
            return true;
        }

        $path = (string) ($file['tmp_name'] ?? '');
        
        if (!file_exists($path) || !is_readable($path)) {
            return true;
        }

        $pageCount = $this->getPageCount($path);

        if ($pageCount === null) {
            // Skip blocking if page count cannot be determined
            return true;
        }

        if ($pageCount > $maxPages) {
            $this->addError("The PDF contains {$pageCount} pages, which exceeds the maximum limit of {$maxPages} pages.");
            return false;
        }

        $this->pdfInfo['pages'] = $pageCount;

        return true;
    }

    /**
     * Extracts total page count from a PDF file using available CLI tools or regex scanning.
     *
     * @param string $path
     * @return int|null
     */
    private function getPageCount(string $path): ?int
    {
        // Strategy 1: pdftk command line utility
        if ($this->isPdftkAvailable()) {
            $output = (string) shell_exec("pdftk " . escapeshellarg($path) . " dump_data 2>/dev/null");
            if (preg_match('/NumberOfPages:\s*(\d+)/', $output, $matches)) {
                return (int) $matches[1];
            }
        }

        // Strategy 2: pdftotext form-feed count
        if ($this->isPdfToTextAvailable()) {
            $output = (string) shell_exec("pdftotext -q " . escapeshellarg($path) . " - 2>&1");
            $pageCount = substr_count($output, "\f") + 1;
            if ($pageCount > 0) {
                return $pageCount;
            }
        }

        // Strategy 3: Direct stream parsing
        if ($count = $this->countPdfPages($path)) {
            return $count;
        }

        return null;
    }

    /**
     * Counts page object entries in PDF source code via regex scanning.
     *
     * @param string $path
     * @return int|null
     */
    private function countPdfPages(string $path): ?int
    {
        $content = file_get_contents($path);
        if (!$content) {
            return null;
        }

        // Count `/Type /Page` occurrences in uncompressed PDF blocks
        preg_match_all('/\/Type\s*\/Page\b/', $content, $matches);
        
        $count = count($matches[0]);
        
        return $count > 0 ? $count : null;
    }

    /**
     * Checks if `pdftk` system CLI tool is executable.
     *
     * @return bool
     */
    private function isPdftkAvailable(): bool
    {
        $output = shell_exec('command -v pdftk 2>/dev/null');
        return !empty($output);
    }

    /**
     * Checks if `pdftotext` system CLI tool is executable.
     *
     * @return bool
     */
    private function isPdfToTextAvailable(): bool
    {
        $output = shell_exec('command -v pdftotext 2>/dev/null');
        return !empty($output);
    }

    /**
     * Appends error message to internal stack and triggers logger.
     *
     * @param string $message
     * @return void
     */
    private function addError(string $message): void
    {
        $this->errors[] = $message;
        $this->log($message);
    }

    /**
     * Returns primary validation message with formatted placeholders replaced.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{max}', '{min}', '{ext}'],
                [
                    $this->formatSize($this->options['max'] ?? 0),
                    $this->formatSize($this->options['min'] ?? 0),
                    implode(', ', $this->options['ext'] ?? [])
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The file must be a valid PDF document.";
    }

    /**
     * Gets all recorded error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns extracted PDF metadata properties.
     *
     * @return array<string, mixed>
     */
    public function getPdfInfo(): array
    {
        return $this->pdfInfo;
    }
}