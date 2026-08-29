<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class JsonValidator
 *
 * Validates and cleans JSON strings with comprehensive support for:
 * - JSON syntax validation
 * - Decoding configuration options (associative array vs object, depth limits)
 * - Schema structure validation (required keys, nested properties, regex patterns, defaults)
 * - Data type restriction checking
 * - Detailed native JSON error mapping
 * - Automatic empty-value pruning/sanitization
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class JsonValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'json';

    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Decoded payload produced upon successful JSON validation.
     *
     * @var array<mixed, mixed>
     */
    protected array $validatedData = [];

    /**
     * Returns the array configuration schema for supported rule options.
     *
     * @return array<string, array{
     *     required: bool,
     *     type: string,
     *     default?: mixed,
     *     function?: callable
     * }>
     */
    public function options(): array
    {
        return [
            // Decode as associative array (true) or stdClass object (false)
            'assoc' => [
                'required' => false,
                'type' => 'bool',
                'default' => true,
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Maximum recursion parsing depth
            'depth' => [
                'required' => false,
                'type' => 'int',
                'default' => 512,
                'function' => fn(string $value) => (int) $value
            ],
            
            // JSON schema definition array (required keys, property types, defaults)
            'schema' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => json_decode($value, true)
            ],
            
            // Allowed root data types (array, object, string, number, boolean, null)
            'types' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('trim', explode(',', $value))
            ],
            
            // Maximum payload length constraint (in raw string characters)
            'maxSize' => [
                'required' => false,
                'type' => 'int',
                'function' => fn(string $value) => (int) $value
            ],
            
            // Strip empty elements recursively (null, '', [], {})
            'clean' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // Custom failure error message template override
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input JSON string against syntax, schema structure, types, and size.
     *
     * @param mixed &$value Target JSON payload. Mutated to decoded data structure on success.
     * @return bool `true` if all enabled validation steps pass, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Payload size check
        if ($this->options['maxSize'] ?? false) {
            if (is_string($value) && strlen($value) > $this->options['maxSize']) {
                $this->addError("The JSON payload exceeds the maximum allowed size of " . $this->options['maxSize'] . " characters.");
                return false;
            }
        }

        // 2. Decode JSON payload
        $assoc = $this->options['assoc'] ?? true;
        $depth = $this->options['depth'] ?? 512;
        
        $decoded = json_decode((string) $value, $assoc, $depth);
        
        // 3. Native syntax/decoding error evaluation
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = $this->getJsonError(json_last_error());
            $this->addError("Invalid JSON: " . $error);
            return false;
        }

        // 4. Root level data type check
        if ($types = $this->options['types'] ?? null) {
            if (!$this->validateTypes($decoded, $types)) {
                return false;
            }
        }

        // 5. Schema verification (if defined)
        if ($schema = $this->options['schema'] ?? null) {
            if (!$this->validateSchema($decoded, $schema)) {
                return false;
            }
        }

        // 6. Data sanitization (if enabled)
        if ($this->options['clean'] ?? false) {
            $decoded = $this->cleanData($decoded);
        }

        // 7. Update target reference with validated data structure
        $value = $decoded;
        $this->validatedData = (array) $decoded;
        
        return true;
    }

    /**
     * Validates root payload against candidate allowed data types.
     *
     * @param mixed $data Decoded JSON data structure.
     * @param array<int, string> $allowedTypes Candidate allowed types.
     * @return bool
     */
    private function validateTypes(mixed $data, array $allowedTypes): bool
    {
        $types = ['array', 'object', 'string', 'number', 'boolean', 'null'];
        
        foreach ($allowedTypes as $type) {
            if (!in_array($type, $types, true)) {
                $this->addError("The type '{$type}' is not recognized. Allowed types: " . implode(', ', $types));
                return false;
            }
        }

        $dataType = gettype($data);
        
        if ($dataType === 'double' || $dataType === 'integer') {
            $dataType = 'number';
        }
        
        if (!in_array($dataType, $allowedTypes, true)) {
            $this->addError("The JSON payload must be of type " . implode(' or ', $allowedTypes) . ", but '{$dataType}' was provided.");
            return false;
        }

        return true;
    }

    /**
     * Validates decoded JSON against a structural schema object.
     *
     * @param array<mixed, mixed> &$data Decoded array reference.
     * @param array<string, mixed> $schema Target validation schema structure.
     * @return bool
     */
    private function validateSchema(array &$data, array $schema): bool
    {
        // Required keys validation
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $key) {
                if (!array_key_exists($key, $data)) {
                    $this->addError("The required key '{$key}' is missing from the JSON payload.");
                    return false;
                }
            }
        }

        // Property-level type and regex pattern validation
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                if (array_key_exists($key, $data)) {
                    if (isset($property['type'])) {
                        $types = explode('|', $property['type']);
                        $valueType = gettype($data[$key]);
                        if ($valueType === 'double' || $valueType === 'integer') {
                            $valueType = 'number';
                        }
                        if (!in_array($valueType, $types, true)) {
                            $this->addError("Property '{$key}' must be of type " . implode(' or ', $types) . ", but '{$valueType}' was provided.");
                            return false;
                        }
                    }
                    
                    if (isset($property['pattern']) && is_string($data[$key])) {
                        if (!preg_match($property['pattern'], $data[$key])) {
                            $this->addError("Property '{$key}' does not match the expected format pattern.");
                            return false;
                        }
                    }
                }
            }
        }

        // Fill default key/value fallbacks
        if (isset($schema['defaults']) && is_array($schema['defaults'])) {
            foreach ($schema['defaults'] as $key => $default) {
                if (!array_key_exists($key, $data)) {
                    $data[$key] = $default;
                }
            }
        }

        return true;
    }

    /**
     * Recursively prunes empty elements (null, empty strings, empty arrays, empty objects).
     *
     * @param mixed $data Target node to sanitize.
     * @return mixed Cleaned data structure.
     */
    private function cleanData(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = $this->cleanData($value);
                }
                
                if (empty($data[$key]) && $data[$key] !== 0 && $data[$key] !== false) {
                    unset($data[$key]);
                }
            }
            
            return $data;
        }
        
        if (is_object($data)) {
            $array = (array) $data;
            foreach ($array as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $array[$key] = $this->cleanData($value);
                }
                
                if (empty($array[$key]) && $array[$key] !== 0 && $array[$key] !== false) {
                    unset($array[$key]);
                }
            }
            return (object) $array;
        }
        
        return $data;
    }

    /**
     * Maps PHP native JSON error code constants to descriptive English error messages.
     *
     * @param int $errorCode
     * @return string
     */
    private function getJsonError(int $errorCode): string
    {
        return match ($errorCode) {
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded.',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON (state mismatch).',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character encountered.',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON.',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded.',
            JSON_ERROR_RECURSION => 'Recursive references detected in string.',
            JSON_ERROR_INF_OR_NAN => 'One or more INF or NaN values specified.',
            JSON_ERROR_UNSUPPORTED_TYPE => 'A value of an unsupported type was given.',
            default => 'Unknown JSON error occurred (code: ' . $errorCode . ').'
        };
    }

    /**
     * Appends an error message to the internal collection and logs it.
     *
     * @param string $message Error message string.
     * @return void
     */
    private function addError(string $message): void
    {
        $this->errors[] = $message;
        $this->log($message);
    }

    /**
     * Retrieves the primary validation error message, formatted or default.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;
        
        if ($customMessage) {
            return str_replace('{value}', $this->parameter, $customMessage);
        }

        return $this->errors[0] ?? "The field '{$this->parameter}' must contain valid JSON.";
    }

    /**
     * Retrieves all recorded validation error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retrieves the decoded data structure produced during validation.
     *
     * @return array<mixed, mixed>
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }
}