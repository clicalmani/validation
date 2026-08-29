<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class IDValidator
 *
 * Validates the existence of a given ID or key in the database for a specific model.
 * 
 * Key Features:
 * - Supports custom target column checks (e.g., slug, code, UUID).
 * - Handles both single and composite primary keys seamlessly using the Key class.
 * - Supports soft-deleted records inclusion (`withTrashed`).
 * - Supports custom query scoping (e.g., multi-tenant checks, status constraints).
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class IDValidator extends Rule
{
    use ResolvesModel;

    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'id';

    /**
     * Fully qualified class name of the target model.
     *
     * @var string
     */
    protected string $model;

    /**
     * Target model's primary key metadata.
     *
     * @var mixed
     */
    protected $primaryKey;

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
            // Target Model name (e.g., 'user_profile' -> UserProfile)
            'model' => [
                'required' => true,
                'type' => 'string',
                'function' => fn(string $model) => collect(explode('_', $model))
                    ->map(fn(string $part) => ucfirst($part))
                    ->join('')
            ],

            // Column name to check against. Defaults to the model's primary key.
            // Useful for validating alternate unique keys like 'slug' or 'code'.
            'column' => [
                'required' => false,
                'type' => 'string',
            ],

            // Additional conditions to append (via AND) to the existence query.
            // Accepts array of column => value or column => callable (evaluated during validation).
            // Example: ['company_id' => fn() => auth()->company_id]
            'scope' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => json_decode($value, true)
            ],

            // If true, includes soft-deleted records in existence lookup
            // (Ignored if the target model does not utilize the SoftDelete trait).
            'withTrashed' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
        ];
    }

    /**
     * Validates if the given value exists in the database.
     *
     * @param mixed &$value The value or key representation being validated.
     * @return bool `true` if the record exists, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        if (null === $value || $value === '') {
            return false;
        }

        $this->model = $this->resolveModelClass($this->options['model']);

        /** @var \Clicalmani\Database\Factory\Models\Elegant $instance */
        $instance = new $this->model;

        $column = $this->options['column'] ?? null;

        [$criteria, $bindings] = $column
            ? ["`{$column}` = ?", [$value]]
            : $this->buildKeyCriteria($instance, $value);

        [$criteria, $bindings] = $this->applyScope($criteria, $bindings);

        $query = $this->model::where($criteria, $bindings);

        if (($this->options['withTrashed'] ?? false) && method_exists($instance, 'withTrashed')) {
            $query->withTrashed();
        }

        return null !== $query->first();
    }

    /**
     * Builds the SQL criteria condition and parameter bindings for the model's primary key
     * (supports both single and composite primary keys).
     *
     * @param \Clicalmani\Database\Factory\Models\Elegant $instance Model instance.
     * @param mixed $value Scalar value, or comma-separated string for composite keys.
     * @return array{0: string, 1: array<int, mixed>} Tuple containing [SQL Condition, Bindings].
     */
    private function buildKeyCriteria($instance, mixed $value): array
    {
        /** @var \Clicalmani\Database\Factory\Models\Key $key */
        $key = $instance->getKey();

        // Check isComposite() BEFORE invoking scalarName()/scalarValue()
        // as those methods throw a \LogicException on composite keys.
        if ($key->isComposite()) {
            $criteria = collect($key->names())
                ->map(fn(string $k) => "`{$k}` = ?")
                ->join(' AND ');
            $bindings = explode(',', (string) $value);

            return [$criteria, $bindings];
        }

        return ["`{$key->scalarName()}` = ?", [$value]];
    }

    /**
     * Appends additional scoping constraints defined in the 'scope' option to the SQL query.
     *
     * @param string $criteria Existing SQL criteria string.
     * @param array<int, mixed> $bindings Existing SQL parameter bindings.
     * @return array{0: string, 1: array<int, mixed>} Tuple containing [Updated SQL Criteria, Updated Bindings].
     */
    private function applyScope(string $criteria, array $bindings): array
    {
        $scope = $this->options['scope'] ?? [];

        foreach ($scope as $column => $scopedValue) {
            $criteria .= " AND `{$column}` = ?";
            $bindings[] = is_callable($scopedValue) ? $scopedValue() : $scopedValue;
        }

        return [$criteria, $bindings];
    }
}