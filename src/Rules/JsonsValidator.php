<?php

namespace Clicalmani\Validation\Rules;

use Exception;

/**
 * Class JsonsValidator
 * 
 * Validates an array of JSON strings or structures with support for:
 * - JSON array inputs (array of JSON strings or structures)
 * - Individual element validation
 * - Decoding configurations (associative array vs object, max depth)
 * - Automatic empty item filtering
 * - Min/max collection item bounds checking
 * - Deduplication (distinct items)
 * - Custom item validator delegation
 * - Detailed error reporting (per-index tracking)
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class JsonsValidator extends JsonValidator
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'json[]';

    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Collection of successfully validated elements.
     *
     * @var array<int, mixed>
     */
    protected array $validItems = [];

    /**
     * Details regarding failed elements indexed by position.
     *
     * @var array<int, array{index: int|string, value: mixed, error: string}>
     */
    protected array $invalidItems = [];

    /**
     * Returns the merged configuration schema for array-based JSON validation options.
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
        // Inherit parent JSON options
        $options = parent::options();
        
        // Remove scalar/root JSON-only options irrelevant to arrays
        unset($options['types'], $options['schema'], $options['maxSize']);
        
        // Add array collection specific options
        $options['maxItems'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        $options['minItems'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        $options['distinct'] = [
            'required' => false,
            'type' => 'bool',
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        $options['allowEmpty'] = [
            'required' => false,
            'type' => 'bool',
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        $options['itemValidator'] = [
            'required' => false,
            'type' => 'string'
        ];

        return $options;
    }

    /**
     * Validates an array of JSON payload elements.
     *
     * @param mixed &$value Target array or JSON string. Mutated to valid elements array on success.
     * @return bool `true` if all elements pass validation, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        // 1. Input normalization
        $value = $this->normalizeInput($value);
        
        if ($value === null) {
            $this->addError("The provided JSON array is invalid.");
            return false;
        }

        // 2. Empty values filtering
        if (!($this->options['allowEmpty'] ?? false)) {
            $value = array_filter($value, function ($item) {
                return !empty($item) || $item === '0' || $item === 0;
            });
        }

        // 3. Count constraints check (minItems / maxItems)
        if (!$this->validateCount($value)) {
            return false;
        }

        // 4. Duplicate removal
        if ($this->options['distinct'] ?? false) {
            $value = array_unique($value);
        }

        // 5. Individual element validation loop
        $this->validItems = [];
        $this->invalidItems = [];
        $this->errors = [];

        foreach ($value as $index => $item) {
            // Check for custom validator delegate
            if ($customValidator = $this->options['itemValidator'] ?? null) {
                $result = $this->validateWithCustomValidator($item, $customValidator);
            } else {
                $result = $this->validateItem($item);
            }

            if ($result) {
                $this->validItems[] = $item;
            } else {
                $lastError = $this->getLastError();
                $this->invalidItems[] = [
                    'index' => $index,
                    'value' => $item,
                    'error' => $lastError
                ];
                $this->errors[] = "Item at index {$index} is invalid: " . $lastError;
            }
        }

        // 6. Final state evaluation
        if (!empty($this->errors)) {
            if (count($this->errors) === 1) {
                $this->addError($this->errors[0]);
            } else {
                $this->addError(count($this->errors) . " invalid JSON items out of " . count($value));
            }
            return false;
        }

        // 7. Update target reference with validated collection
        $value = $this->validItems;

        return true;
    }

    /**
     * Normalizes heterogeneous inputs into a validated array.
     *
     * @param mixed $value Raw array, CSV string, or JSON array payload.
     * @return array<mixed>|null
     */
    private function normalizeInput(mixed $value): ?array
    {
        // Already an array
        if (is_array($value)) {
            return $value;
        }

        // String payload handling
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            
            // Attempt comma-separated values split
            if (str_contains($value, ',')) {
                return array_map('trim', explode(',', $value));
            }
            
            // Single scalar string wrap
            return [$value];
        }

        // Numeric or boolean scalar wrap
        if (is_numeric($value) || is_bool($value)) {
            return [$value];
        }

        return null;
    }

    /**
     * Validates item count constraints against minItems and maxItems.
     *
     * @param array<mixed> $value Collection to measure.
     * @return bool
     */
    private function validateCount(array $value): bool
    {
        $count = count($value);

        if ($min = $this->options['minItems'] ?? null) {
            if ($count < $min) {
                $this->addError("The array must contain at least {$min} item(s), {$count} provided.");
                return false;
            }
        }

        if ($max = $this->options['maxItems'] ?? null) {
            if ($count > $max) {
                $this->addError("The array must contain at most {$max} item(s), {$count} provided.");
                return false;
            }
        }

        return true;
    }

    /**
     * Validates an individual element using parent JSON validation rules.
     *
     * @param mixed &$item Target element reference.
     * @return bool
     */
    private function validateItem(mixed &$item): bool
    {
        // Backup parent configuration context
        $originalOptions = $this->options;
        
        // Temporary options configuration for single item evaluation
        $this->options['types'] = ['array', 'object', 'string', 'number', 'boolean', 'null'];
        
        // String elements decoded/validated as JSON payload
        if (is_string($item)) {
            $result = parent::validate($item);
        } else {
            // Direct native type check for non-string types
            $result = $this->validateValueType($item);
        }
        
        // Restore parent options context
        $this->options = $originalOptions;
        
        return $result;
    }

    /**
     * Validates an item delegating execution to a custom rule class.
     *
     * @param mixed $item
     * @param string $validatorClass Fully qualified class name.
     * @return bool
     */
    private function validateWithCustomValidator(mixed $item, string $validatorClass): bool
    {
        if (!class_exists($validatorClass)) {
            $this->addError("The custom validator class '{$validatorClass}' does not exist.");
            return false;
        }

        try {
            /** @var Rule $validator */
            $validator = new $validatorClass();
            return $validator->validate($item);
        } catch (Exception $e) {
            $this->addError("Error in custom validator: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validates candidate non-JSON raw data types.
     *
     * @param mixed $value
     * @return bool
     */
    private function validateValueType(mixed $value): bool
    {
        $allowedTypes = ['array', 'object', 'string', 'integer', 'double', 'boolean', 'NULL', 'number'];
        $type = gettype($value);
        
        if ($type === 'double' || $type === 'integer') {
            $type = 'number';
        }
        
        if (!in_array($type, $allowedTypes, true)) {
            $this->addError("Type '{$type}' is not supported. Allowed types: " . implode(', ', $allowedTypes));
            return false;
        }

        return true;
    }

    /**
     * Retrieves the most recent error message appended to the collection.
     *
     * @return string
     */
    private function getLastError(): string
    {
        $errors = $this->getErrors();
        return end($errors) ?: "Unknown error";
    }

    /**
     * Appends an error message to internal errors array and logs it.
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
     * Formats and returns the primary validation error message.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{count}', '{errors}'],
                [(string) count($this->errors), implode('; ', $this->errors)],
                $customMessage
            );
        }

        if (empty($this->errors)) {
            return null;
        }

        if (count($this->errors) === 1) {
            return $this->errors[0];
        }

        return count($this->errors) . " invalid JSON item(s): " . implode('; ', $this->errors);
    }

    /**
     * Gets all recorded validation error messages.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Gets the collection of elements that successfully passed validation.
     *
     * @return array<int, mixed>
     */
    public function getValidItems(): array
    {
        return $this->validItems;
    }

    /**
     * Gets the collection of elements that failed validation along with index/error details.
     *
     * @return array<int, array{index: int|string, value: mixed, error: string}>
     */
    public function getInvalidItems(): array
    {
        return $this->invalidItems;
    }
}