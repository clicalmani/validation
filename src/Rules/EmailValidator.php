<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class EmailValidator
 *
 * Validates email addresses with customizable validation checks:
 * - Database uniqueness verification
 * - RFC 5322 email format validation
 * - Mail Exchanger (MX) record lookup
 * - Domain Name System (DNS) record validation
 * - Domain blacklisting (blocked domains)
 * - Domain whitelisting (allowed domains)
 *
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class EmailValidator extends Rule
{
    use ResolvesModel;
    use NormalizesModel;

    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'email';

    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

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
            // Database Uniqueness: Model name (e.g., 'user' -> App\Models\User)
            'unique' => [
                'required' => false,
                'type' => 'string',
                'function' => fn(string $model) => $this->normalizeModelName($model)
            ],
            
            // Database column to check for uniqueness (defaults to the field parameter name)
            'column' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Primary key or value to exclude from uniqueness check (useful for update operations)
            'except' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Column name for record exclusion (defaults to 'id')
            'exceptColumn' => [
                'required' => false,
                'type' => 'string',
                'default' => 'id'
            ],
            
            // MX Record validation flag
            'mx' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // DNS Record validation flag
            'dns' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],
            
            // List of disallowed email domains
            'block' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('trim', explode(',', $value))
            ],
            
            // List of exclusively permitted email domains
            'only' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('trim', explode(',', $value))
            ],
            
            // Custom error message template
            'message' => [
                'required' => false,
                'type' => 'string'
            ],
            
            // Target Model Namespace
            'namespace' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates the given email input against configured criteria.
     *
     * @param mixed &$email The email address input to validate.
     * @return bool `true` if all enabled validation steps pass, `false` otherwise.
     */
    public function validate(mixed &$email): bool
    {
        $email = $this->parseString($email);

        if (empty($email)) {
            $this->addError('The email address is required.');
            return false;
        }

        // 1. Format validation
        if (!$this->validateFormat($email)) {
            $this->addError($this->getErrorMessage($email, 'format'));
            return false;
        }

        // 2. Domain checks (MX / DNS)
        $domain = substr($email, strrpos($email, '@') + 1);

        if ($this->options['mx'] ?? false) {
            if (!$this->validateMX($domain)) {
                $this->addError("The domain '{$domain}' does not have valid mail server (MX) records.");
                return false;
            }
        }

        if ($this->options['dns'] ?? false) {
            if (!$this->validateDNS($domain)) {
                $this->addError("The domain '{$domain}' is invalid or unresolvable.");
                return false;
            }
        }

        // 3. Blocked domains check
        if ($blocked = $this->options['block'] ?? []) {
            if (in_array($domain, $blocked, true)) {
                $this->addError("The domain '{$domain}' is not allowed.");
                return false;
            }
        }

        // 4. Allowed domains check
        if ($allowed = $this->options['only'] ?? []) {
            if (!in_array($domain, $allowed, true)) {
                $this->addError("Only the following domains are allowed: " . implode(', ', $allowed));
                return false;
            }
        }

        // 5. Database uniqueness validation
        if ($this->options['unique'] ?? false) {
            if (!$this->validateUnique($email)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates the syntax of the email address according to RFC 5322 specs.
     *
     * @param string $email The target email address string.
     * @return bool
     */
    private function validateFormat(string $email): bool
    {
        return !!filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Verifies if the domain has active MX (Mail Exchanger) DNS records.
     *
     * @param string $domain The email domain component.
     * @return bool
     */
    private function validateMX(string $domain): bool
    {
        $mxRecords = [];
        getmxrr($domain, $mxRecords);
        return !empty($mxRecords);
    }

    /**
     * Checks if the domain exists and resolves valid DNS records.
     *
     * @param string $domain The email domain component.
     * @return bool
     */
    private function validateDNS(string $domain): bool
    {
        return checkdnsrr($domain, 'ANY') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA');
    }

    /**
     * Checks database uniqueness for the provided email address.
     *
     * @param string $email The target email address.
     * @return bool `true` if unique, `false` if already in use or query fails.
     */
    private function validateUnique(string $email): bool
    {
        try {
            $modelName = $this->resolveModelClass(
                $this->options['unique']
            );

            $column = $this->options['column'] ?? $this->parameter;
            $exceptColumn = $this->options['exceptColumn'] ?? 'id';

            $query = $modelName::where("`{$column}` = ?", [$email]);

            // Exclude current record when updating
            if ($except = $this->options['except'] ?? null) {
                $query->where("`{$exceptColumn}` <> ?", [$except]);
            }

            if (null !== $query->first()) {
                $this->addError("The email address '{$email}' is already in use.");
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->addError("Error checking email uniqueness: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registers a new error message and logs it to system logs.
     *
     * @param string $message The error message string.
     * @return void
     */
    private function addError(string $message): void
    {
        $this->errors[] = $message;
        $this->log($message);
    }

    /**
     * Resolves the appropriate error message based on custom options or failure type.
     *
     * @param string $email The evaluated email input.
     * @param string $type Error category indicator.
     * @return string
     */
    private function getErrorMessage(string $email, string $type = 'format'): string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace('{value}', $email, $customMessage);
        }

        return match ($type) {
            'format' => "The email address '{$email}' is invalid.",
            default => "The email address is invalid."
        };
    }

    /**
     * Retrieves all recorded error messages for this rule.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retrieves the primary (first) error message encountered.
     *
     * @return string|null The first error message string, or `null` if validation passed.
     */
    public function message(): ?string
    {
        return $this->errors[0] ?? null;
    }
}