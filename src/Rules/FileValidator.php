<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class FileValidator
 * 
 * Validates uploaded files with support for:
 * - File size limits (min, max)
 * - Allowed file extensions
 * - Allowed MIME types
 * - Image dimensions (min/max width and height)
 * - Filename regex matching
 * - Multiple file upload structures
 * - Detailed error reporting and message placeholding
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class FileValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'file';
    
    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Array structure of the normalized valid file data.
     *
     * @var array<string, mixed>
     */
    protected array $validFile = [];

    /**
     * Returns the array configuration schema for supported rule options.
     *
     * @return array<string, array{
     *     required: bool,
     *     type: string,
     *     function?: callable
     * }>
     */
    public function options(): array
    {
        return [
            // Maximum file size (parsed to bytes)
            'max' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => $this->parseSize($value)
            ],
            
            // Minimum file size (parsed to bytes)
            'min' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => $this->parseSize($value)
            ],
            
            // Allowed file extensions
            'ext' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('strtolower', array_map('trim', explode(',', $value)))
            ],
            
            // Allowed MIME types
            'mime' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('strtolower', array_map('trim', explode(',', $value)))
            ],
            
            // Minimum image width (in pixels)
            'minWidth' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Maximum image width (in pixels)
            'maxWidth' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Minimum image height (in pixels)
            'minHeight' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Maximum image height (in pixels)
            'maxHeight' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Regex pattern constraint for filename
            'filename' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Allow empty or un-uploaded file inputs
            'allowEmpty' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Custom error message format
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input target file against specified criteria.
     *
     * @param mixed &$value Target file array or object reference.
     * @return bool `true` if validation passes, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. File existence check
        if (!$this->isValidFile($value)) {
            if ($this->options['allowEmpty'] ?? false) {
                return true;
            }
            $this->addError("No file was uploaded or the uploaded file is invalid.");
            return false;
        }

        // 2. Normalize file data structure
        $file = $this->normalizeFile($value);
        $this->validFile = $file;

        // 3. Validate native upload errors
        if (!$this->validateUploadError($file)) {
            return false;
        }

        // 4. Validate file size constraints
        if (!$this->validateSize($file)) {
            return false;
        }

        // 5. Validate file extension constraints
        if (!$this->validateExtension($file)) {
            return false;
        }

        // 6. Validate MIME type constraints
        if (!$this->validateMime($file)) {
            return false;
        }

        // 7. Validate image dimensions constraints
        if (!$this->validateDimensions($file)) {
            return false;
        }

        // 8. Validate filename pattern
        if (!$this->validateFilename($file)) {
            return false;
        }

        // 9. Update input reference to normalized array
        $value = $this->validFile;

        return true;
    }

    /**
     * Checks whether the input value represents a valid non-empty file upload.
     *
     * @param mixed $value
     * @return bool
     */
    private function isValidFile(mixed $value): bool
    {
        if (is_null($value) || $value === '') {
            return false;
        }

        // Handle array representation of file inputs
        if (is_array($value)) {
            if (isset($value['error'])) {
                return $value['error'] !== UPLOAD_ERR_NO_FILE;
            }
            
            // Check nested file collections
            foreach ($value as $item) {
                if ($this->isValidFile($item)) {
                    return true;
                }
            }
            return false;
        }

        // Handle object representations (e.g., PSR-7 UploadedFile or Symfony File)
        if (is_object($value)) {
            if (method_exists($value, 'isValid')) {
                return $value->isValid();
            }
            if (method_exists($value, 'getError')) {
                return $value->getError() === UPLOAD_ERR_OK;
            }
        }

        return false;
    }

    /**
     * Normalizes native arrays or object representations into a standard file array structure.
     *
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function normalizeFile(mixed $value): array
    {
        // Standard PHP $_FILES array
        if (is_array($value) && isset($value['name'], $value['size'], $value['tmp_name'])) {
            return $value;
        }

        // UploadedFile object representation
        if (is_object($value)) {
            return [
                'name' => $this->getObjectProperty($value, 'name', 'getClientOriginalName', 'getClientFilename'),
                'tmp_name' => $this->getObjectProperty($value, 'tmp_name', 'getPathname', 'getRealPath'),
                'size' => $this->getObjectProperty($value, 'size', 'getSize'),
                'type' => $this->getObjectProperty($value, 'type', 'getClientMediaType', 'getMimeType'),
                'error' => $this->getObjectProperty($value, 'error', 'getError'),
            ];
        }

        return [];
    }

    /**
     * Dynamic helper to extract object property values via accessor methods or direct access.
     *
     * @param object $object
     * @param string $property
     * @param string ...$methods
     * @return mixed
     */
    private function getObjectProperty(object $object, string $property, string ...$methods): mixed
    {
        foreach ($methods as $method) {
            if (method_exists($object, $method)) {
                return $object->$method();
            }
        }

        if (property_exists($object, $property)) {
            return $object->$property;
        }

        return null;
    }

    /**
     * Validates native PHP upload status codes.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateUploadError(array $file): bool
    {
        $error = $file['error'] ?? UPLOAD_ERR_OK;

        if ($error !== UPLOAD_ERR_OK) {
            $message = $this->getUploadErrorMessage($error);
            $this->addError($message);
            return false;
        }

        return true;
    }

    /**
     * Translates PHP native UPLOAD_ERR constants into human-readable error messages.
     *
     * @param int $error
     * @return string
     */
    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the maximum size limit allowed by the server.",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the maximum size limit specified in the HTML form.",
            UPLOAD_ERR_PARTIAL => "The file was only partially uploaded.",
            UPLOAD_ERR_NO_FILE => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary directory on the server.",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload process.",
            default => "An unknown error occurred during the file upload."
        };
    }

    /**
     * Validates minimum and maximum file size requirements.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateSize(array $file): bool
    {
        $size = $file['size'] ?? 0;

        if ($min = $this->options['min'] ?? null) {
            if ($size < $min) {
                $this->addError("The file size is too small. Minimum required size: " . $this->formatSize($min));
                return false;
            }
        }

        if ($max = $this->options['max'] ?? null) {
            if ($size > $max) {
                $this->addError("The file size is too large. Maximum allowed size: " . $this->formatSize($max));
                return false;
            }
        }

        return true;
    }

    /**
     * Validates file extension against allowed extension lists.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateExtension(array $file): bool
    {
        $extensions = $this->options['ext'] ?? null;

        if (!$extensions) {
            return true;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if (!in_array($extension, $extensions, true)) {
            $this->addError("The extension '{$extension}' is not allowed. Allowed extensions: " . implode(', ', $extensions));
            return false;
        }

        return true;
    }

    /**
     * Validates file MIME types against allowed MIME type rules.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateMime(array $file): bool
    {
        $mimes = $this->options['mime'] ?? null;

        if (!$mimes) {
            return true;
        }

        $mimeType = $file['type'] ?? (file_exists($file['tmp_name'] ?? '') ? mime_content_type($file['tmp_name']) : '');

        if (!in_array(strtolower((string) $mimeType), $mimes, true)) {
            $this->addError("The MIME type '{$mimeType}' is not allowed. Allowed types: " . implode(', ', $mimes));
            return false;
        }

        return true;
    }

    /**
     * Validates pixel dimensions for image files.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateDimensions(array $file): bool
    {
        $mimeType = (string) ($file['type'] ?? '');
        if (!str_starts_with($mimeType, 'image/')) {
            return true;
        }

        $path = (string) ($file['tmp_name'] ?? '');
        if (!file_exists($path)) {
            return true;
        }

        $size = getimagesize($path);
        if (!$size) {
            return true;
        }

        $width = $size[0];
        $height = $size[1];

        // Validate Width
        if ($minWidth = $this->options['minWidth'] ?? null) {
            if ($width < $minWidth) {
                $this->addError("The image width must be at least {$minWidth}px.");
                return false;
            }
        }

        if ($maxWidth = $this->options['maxWidth'] ?? null) {
            if ($width > $maxWidth) {
                $this->addError("The image width must not exceed {$maxWidth}px.");
                return false;
            }
        }

        // Validate Height
        if ($minHeight = $this->options['minHeight'] ?? null) {
            if ($height < $minHeight) {
                $this->addError("The image height must be at least {$minHeight}px.");
                return false;
            }
        }

        if ($maxHeight = $this->options['maxHeight'] ?? null) {
            if ($height > $maxHeight) {
                $this->addError("The image height must not exceed {$maxHeight}px.");
                return false;
            }
        }

        return true;
    }

    /**
     * Validates original filename using regex pattern match.
     *
     * @param array<string, mixed> $file
     * @return bool
     */
    private function validateFilename(array $file): bool
    {
        $pattern = $this->options['filename'] ?? null;

        if (!$pattern) {
            return true;
        }

        $filename = (string) ($file['name'] ?? '');

        if (!preg_match($pattern, $filename)) {
            $this->addError("The filename does not match the required format pattern.");
            return false;
        }

        return true;
    }

    /**
     * Parses human-readable size notations (K, M, G) into byte integers.
     *
     * @param string $value
     * @return int
     */
    private function parseSize(string $value): int
    {
        $value = strtoupper($value);
        
        if (preg_match('/^(\d+)([KMG])?$/', $value, $matches)) {
            $size = (int) $matches[1];
            $unit = $matches[2] ?? '';
            
            return match ($unit) {
                'K' => $size * 1024,
                'M' => $size * 1024 * 1024,
                'G' => $size * 1024 * 1024 * 1024,
                default => $size
            };
        }
        
        return (int) $value;
    }

    /**
     * Formats bytes to human-readable memory string format.
     *
     * @param int $bytes
     * @return string
     */
    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Records error message to local stack and logs message.
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
     * Returns formatted error message replacing placeholders if customized.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{max}', '{min}', '{ext}', '{mime}'],
                [
                    $this->formatSize($this->options['max'] ?? 0),
                    $this->formatSize($this->options['min'] ?? 0),
                    implode(', ', $this->options['ext'] ?? []),
                    implode(', ', $this->options['mime'] ?? [])
                ],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The uploaded file is invalid.";
    }

    /**
     * Returns all recorded validation error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns normalized validated file structure.
     *
     * @return array<string, mixed>
     */
    public function getValidFile(): array
    {
        return $this->validFile;
    }
}