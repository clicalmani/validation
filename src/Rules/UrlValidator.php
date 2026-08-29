<?php

namespace Clicalmani\Validation\Rules;

use Clicalmani\Validation\Rule;

/**
 * Class UrlValidator
 * 
 * Validates URLs with support for:
 * - Basic URL format validation (FILTER_VALIDATE_URL)
 * - Allowed scheme constraints (http, https, ftp, etc.)
 * - Whitelisted and blacklisted domain checks
 * - Port restrictions and standard scheme port enforcement
 * - DNS record resolution checks (A, AAAA, MX)
 * - Automatic pre-validation sanitization and default scheme prepend
 * - Detailed error messaging and access to parsed URL components
 * 
 * @package Clicalmani\Validation\Rules
 * @author clicalmani
 */
class UrlValidator extends Rule
{
    /**
     * The argument identifier associated with this validation rule.
     *
     * @var string
     */
    protected static string $argument = 'url';

    /**
     * List of validation error messages encountered during execution.
     *
     * @var array<int, string>
     */
    protected array $errors = [];

    /**
     * Parsed structure components of the validated URL.
     *
     * @var array<string, mixed>
     */
    protected array $parsedUrl = [];

    /**
     * Returns the configuration option schema for the validator rule.
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
        return [
            // Allowed URL schemes (e.g., http, https)
            'schemes' => [
                'required' => false,
                'type' => 'array',
                'default' => ['http', 'https'],
                'function' => fn(string $value) => array_map('trim', explode(',', $value))
            ],

            // Whitelisted target domains
            'allowedDomains' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('trim', explode(',', $value))
            ],

            // Blacklisted restricted domains
            'blockedDomains' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('trim', explode(',', $value))
            ],

            // Explicitly permitted target ports
            'allowedPorts' => [
                'required' => false,
                'type' => 'array',
                'function' => fn(string $value) => array_map('intval', explode(',', $value))
            ],

            // Enable active DNS resolution check
            'dns' => [
                'required' => false,
                'type' => 'bool',
                'function' => fn(string $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ],

            // Pre-validation value sanitization
            'sanitize' => [
                'required' => false,
                'type' => 'string',
                'validator' => fn(string $value) => in_array($value, ['trim', 'lower'], true)
            ],

            // Custom error message pattern
            'message' => [
                'required' => false,
                'type' => 'string'
            ]
        ];
    }

    /**
     * Validates input URL string against defined options and format specifications.
     *
     * @param mixed &$value Reference to the input data being validated.
     * @return bool `true` if valid, `false` otherwise.
     */
    public function validate(mixed &$value): bool
    {
        $this->errors = [];
        $this->parsedUrl = [];

        // 1. Sanitize input value
        $sanitized = $this->sanitizeValue($value);

        if ($sanitized === null) {
            $this->addError("The provided URL is invalid.");
            return false;
        }

        // 2. Validate standard URL format
        if (!$this->validateFormat($sanitized)) {
            return false;
        }

        // 3. Validate URL scheme
        if (!$this->validateSchemes($sanitized)) {
            return false;
        }

        // 4. Validate host domain restrictions
        if (!$this->validateDomain($sanitized)) {
            return false;
        }

        // 5. Validate port permissions
        if (!$this->validatePort($sanitized)) {
            return false;
        }

        // 6. Optional DNS record verification
        if ($this->options['dns'] ?? false) {
            if (!$this->validateDNS($sanitized)) {
                return false;
            }
        }

        // 7. Reconstruct normalized URL output payload
        $value = $this->normalizeUrl($sanitized);

        return true;
    }

    /**
     * Sanitizes raw value and prepends default scheme if missing.
     *
     * @param mixed $value
     * @return string|null
     */
    private function sanitizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            $sanitize = $this->options['sanitize'] ?? null;

            switch ($sanitize) {
                case 'trim':
                    $value = trim($value);
                    break;
                case 'lower':
                    $value = mb_strtolower($value, 'UTF-8');
                    break;
            }

            // Prepend default scheme if scheme component is missing
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//', $value)) {
                $value = 'https://' . $value;
            }

            return $value;
        }

        return null;
    }

    /**
     * Validates standard structure and parses URL elements.
     *
     * @param string $value
     * @return bool
     */
    private function validateFormat(string $value): bool
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->addError("The URL '{$value}' is not valid.");
            return false;
        }

        $parsed = parse_url($value);

        if ($parsed === false) {
            $this->addError("Unable to parse the URL '{$value}'.");
            return false;
        }

        $this->parsedUrl = $parsed;

        if (empty($parsed['host'])) {
            $this->addError("The URL '{$value}' does not contain a valid domain name.");
            return false;
        }

        return true;
    }

    /**
     * Validates that the URL scheme is permitted.
     *
     * @param string $value
     * @return bool
     */
    private function validateSchemes(string $value): bool
    {
        $schemes = $this->options['schemes'] ?? ['http', 'https'];
        $scheme = $this->parsedUrl['scheme'] ?? null;

        if ($scheme === null) {
            $this->addError("The URL does not contain a scheme (e.g., http, https).");
            return false;
        }

        if (!in_array(strtolower($scheme), $schemes, true)) {
            $this->addError(
                "The scheme '{$scheme}' is not allowed. Allowed schemes: " . implode(', ', $schemes)
            );
            return false;
        }

        return true;
    }

    /**
     * Validates host against domain allow/block lists.
     *
     * @param string $value
     * @return bool
     */
    private function validateDomain(string $value): bool
    {
        $host = $this->parsedUrl['host'] ?? '';

        // Domain whitelist enforcement
        if ($allowed = $this->options['allowedDomains'] ?? null) {
            $isAllowed = false;
            foreach ($allowed as $domain) {
                if ($host === $domain || str_ends_with($host, '.' . ltrim($domain, '.'))) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                $this->addError(
                    "The domain '{$host}' is not allowed. Allowed domains: " . implode(', ', $allowed)
                );
                return false;
            }
        }

        // Domain blacklist enforcement
        if ($blocked = $this->options['blockedDomains'] ?? null) {
            foreach ($blocked as $domain) {
                if ($host === $domain || str_ends_with($host, '.' . ltrim($domain, '.'))) {
                    $this->addError("The domain '{$host}' is blocked.");
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validates assigned or inferred port constraints.
     *
     * @param string $value
     * @return bool
     */
    private function validatePort(string $value): bool
    {
        if (!isset($this->parsedUrl['port'])) {
            return true;
        }

        $port = (int) $this->parsedUrl['port'];
        $allowedPorts = $this->options['allowedPorts'] ?? null;

        if ($allowedPorts && !in_array($port, $allowedPorts, true)) {
            $this->addError(
                "The port '{$port}' is not allowed. Allowed ports: " . implode(', ', $allowedPorts)
            );
            return false;
        }

        $scheme = strtolower($this->parsedUrl['scheme'] ?? 'http');
        $standardPorts = [
            'http' => 80,
            'https' => 443,
            'ftp' => 21,
            'sftp' => 22
        ];

        if (isset($standardPorts[$scheme]) && $port !== $standardPorts[$scheme]) {
            if (!$allowedPorts || !in_array($port, $allowedPorts, true)) {
                $this->addError(
                    "The port '{$port}' is non-standard for the '{$scheme}' scheme. Standard port: {$standardPorts[$scheme]}."
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validates active DNS records for domain resolution.
     *
     * @param string $value
     * @return bool
     */
    private function validateDNS(string $value): bool
    {
        $host = $this->parsedUrl['host'] ?? '';

        if (!checkdnsrr($host, 'A') && !checkdnsrr($host, 'AAAA') && !checkdnsrr($host, 'MX')) {
            $this->addError("The domain '{$host}' does not exist or has no valid DNS records.");
            return false;
        }

        return true;
    }

    /**
     * Reconstructs a clean, normalized string URL from internal parsed components.
     *
     * @param string $value
     * @return string
     */
    private function normalizeUrl(string $value): string
    {
        $parsed = $this->parsedUrl;
        $url = '';

        if (isset($parsed['scheme'])) {
            $url .= strtolower($parsed['scheme']) . '://';
        }

        if (isset($parsed['user'])) {
            $url .= $parsed['user'];
            if (isset($parsed['pass'])) {
                $url .= ':' . $parsed['pass'];
            }
            $url .= '@';
        }

        if (isset($parsed['host'])) {
            $url .= strtolower($parsed['host']);
        }

        if (isset($parsed['port'])) {
            $url .= ':' . $parsed['port'];
        }

        if (isset($parsed['path'])) {
            $url .= $parsed['path'];
        }

        if (isset($parsed['query'])) {
            $url .= '?' . $parsed['query'];
        }

        if (isset($parsed['fragment'])) {
            $url .= '#' . $parsed['fragment'];
        }

        return $url;
    }

    /**
     * Log and track error message.
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
     * Retrieves primary error message for presentation.
     *
     * @return string|null
     */
    public function message(): ?string
    {
        $customMessage = $this->options['message'] ?? null;

        if ($customMessage) {
            return str_replace(
                ['{value}'],
                [(string) $this->parameter],
                $customMessage
            );
        }

        return $this->errors[0] ?? "The URL '{$this->parameter}' is not valid.";
    }

    /**
     * Returns error messages captured during execution.
     *
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns component details of parsed target URL.
     *
     * @return array<string, mixed>
     */
    public function getParsedUrl(): array
    {
        return $this->parsedUrl;
    }

    /**
     * Helper method to perform quick standalone URL validation check.
     *
     * @param string $url
     * @return bool
     */
    public function isValidUrl(string $url): bool
    {
        $this->options = [];
        return $this->validate($url);
    }
}