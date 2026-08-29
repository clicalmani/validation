<?php

namespace Clicalmani\Validation\Rules;

/**
 * Class IDsValidator
 *
 * Validates an array of IDs or a delimited string of IDs.
 * Extends IDValidator to add support for multiple ID inputs.
 *
 * Key Features:
 * - Native array input support.
 * - Delimited string input support (e.g., comma-separated, custom separator, JSON string).
 * - Individual validation of each ID against database existence.
 * - Formatting and transformation options (join, distinct, limit).
 * - Detailed error handling with custom formatting parameters.
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class IDsValidator extends IDValidator
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = "id[]";

    /**
     * List of validation error messages keyed per invalid ID entry.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Collection of successfully validated IDs.
     *
     * @var array<int, mixed>
     */
    protected array $validIds = [];

    /**
     * Returns the merged options configuration schema including parent IDValidator options.
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
        $options = parent::options();
        
        // Remove non-relevant inherited options
        unset($options['translate']);

        // Option to join valid IDs into a delimited string post-validation
        $options['join'] = [
            'required' => false,
            'type' => 'bool',
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        // Option to limit the maximum allowed count of input IDs
        $options['limit'] = [
            'required' => false,
            'type' => 'int',
            'function' => fn(string $value) => (int) $value
        ];

        // Option to remove duplicate IDs from the final result set
        $options['distinct'] = [
            'required' => false,
            'type' => 'bool',
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        // Option to specify custom string delimiter (defaults to comma)
        $options['separator'] = [
            'required' => false,
            'type' => 'string',
            'default' => ','
        ];

        // Option to allow empty ID values in the input payload
        $options['allowEmpty'] = [
            'required' => false,
            'type' => 'bool',
            'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
        ];

        // Custom error message template ({count} and {errors} placeholders supported)
        $options['message'] = [
            'required' => false,
            'type' => 'string'
        ];

        return $options;
    }

    /**
     * Validates multiple IDs provided as array, string, or primitive type.
     *
     * @param mixed &$ids Array or string of IDs to validate. Transformed in place based on options.
     * @return bool `true` if all IDs are valid and pass option checks, `false` otherwise.
     */
    public function validate(mixed &$ids): bool
    {
        // 1. Normalize IDs into a standard array structure
        $ids = $this->normalizeIds($ids);

        if ($ids === null) {
            $this->log("Invalid ID format.");
            return false;
        }

        // 2. Filter out empty IDs unless explicitly allowed
        if (!($this->options['allowEmpty'] ?? false)) {
            $ids = array_filter($ids, function($id) {
                return !empty($id) || $id === '0' || $id === 0;
            });
        }

        if (empty($ids)) {
            $this->log("No valid IDs provided.");
            return false;
        }

        // 3. Validate maximum ID count limit
        if ($limit = $this->options['limit'] ?? null) {
            if (count($ids) > $limit) {
                $this->log("The number of IDs exceeds the maximum limit of {$limit}.");
                return false;
            }
        }

        // 4. Validate each ID individually via parent IDValidator logic
        $this->validIds = [];
        $this->errors = [];

        foreach ($ids as $index => $id) {
            $result = parent::validate($id);
            
            if ($result) {
                $this->validIds[] = $id;
            } else {
                $error = parent::message() ?: "The ID '{$id}' at position " . ($index + 1) . " is invalid.";
                $this->errors[] = $error;
            }
        }

        // 5. Evaluate overall validation result
        if (!empty($this->errors)) {
            $this->log($this->formatErrors($this->errors));
            return false;
        }

        // 6. Deduplicate valid IDs if 'distinct' option is enabled
        if ($this->options['distinct'] ?? false) {
            $this->validIds = array_values(array_unique($this->validIds));
        }

        // 7. Apply final payload formatting (joined string or array)
        if ($this->options['join'] ?? false) {
            $separator = $this->options['separator'] ?? ',';
            $ids = implode($separator, $this->validIds);
        } else {
            $ids = $this->validIds;
        }

        return true;
    }

    /**
     * Normalizes incoming raw inputs into a clean array of IDs.
     *
     * Supports array inputs, comma/delimiter separated strings, JSON encoded arrays, and numeric scalars.
     *
     * @param mixed $ids Raw input payload.
     * @return array<int, mixed>|null Normalized array of IDs, or `null` if the format is unsupported.
     */
    private function normalizeIds(mixed $ids): ?array
    {
        // Array input
        if (is_array($ids)) {
            return $ids;
        }

        // String input handling
        if (is_string($ids)) {
            if (str_contains($ids, '%')) {
                $ids = urldecode($ids);
            }

            $separator = $this->options['separator'] ?? ',';
            
            // JSON string support
            if (str_starts_with($ids, '[') || str_starts_with($ids, '{')) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            return array_map('trim', explode($separator, $ids));
        }

        // Numeric scalar input (int, float)
        if (is_numeric($ids)) {
            return [(string) $ids];
        }

        return null;
    }

    /**
     * Formats collected error messages into a cohesive single output string.
     *
     * @param array<int, string> $errors Collection of individual validation error messages.
     * @return string Formatted composite error message string.
     */
    private function formatErrors(array $errors): string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{count}', '{errors}'],
                [count($errors), implode(', ', $errors)],
                $customMessage
            );
        }

        if (count($errors) === 1) {
            return $errors[0];
        }

        return count($errors) . " invalid IDs: " . implode('; ', $errors);
    }

    /**
     * Retrieves the array of successfully validated IDs.
     *
     * @return array<int, mixed>
     */
    public function getValidIds(): array
    {
        return $this->validIds;
    }

    /**
     * Retrieves all recorded error messages for this rule evaluation.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Resolves the main composite error message for the validation execution.
     *
     * @return string|null Formatted global error message, or `null` if no errors occurred.
     */
    public function message(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $customMessage = $this->options['message'] ?? null;
        
        if ($customMessage) {
            return str_replace(
                ['{count}', '{errors}'],
                [count($this->errors), implode(', ', $this->errors)],
                $customMessage
            );
        }

        return count($this->errors) . " invalid ID(s): " . implode('; ', $this->errors);
    }
}