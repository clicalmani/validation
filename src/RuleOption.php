<?php
namespace Clicalmani\Validation;

use Clicalmani\Validation\Exceptions\ValidationException;

/**
 * RuleOption class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
class RuleOption implements RuleOptionInterface
{
    public function __construct(
        private string $name,
        private mixed &$value,
        private ?bool $is_required = null,
        private ?string $type = null,
        private ?array $keys = [],
        private \Closure|string|array|null $func = null,
        private \Closure|string|array|null $validator = null,
        private mixed $default = null,
        private ?string $rule = null
    )
    {
        // ...
    }

    public function validate(): void
    {
        $rule = $this->rule ?? 'validation';
        
        if ($this->value === null) {
            if ($this->is_required) {
                throw new ValidationException(sprintf('Option %s is required for %s rule.', $this->name, $rule));
            }

            $this->value = $this->default;
            return;
        }

        if ($this->func && is_callable($this->func)) {
            $this->value = call_user_func($this->func, $this->value);
        }

        if ($this->validator && is_callable($this->validator) && !call_user_func($this->validator, $this->value)) {
            throw new ValidationException(sprintf(
                '%s is not a valid value for option %s of the %s rule.',
                is_scalar($this->value) ? (string) $this->value : json_encode($this->value),
                $this->name,
                $rule
            ));
        }

        if ($this->type) {
            try {
                $this->value = (new InputParser())->cast($this->value, $this->type);
            } catch (\InvalidArgumentException $e) {
                throw new ValidationException(sprintf('Option %s of the %s rule: %s', $this->name, $rule, $e->getMessage()));
            }
        }

        if ($this->keys) {
            $result = [];

            foreach ($this->keys as $index => $key) {
                $result[$key] = $this->value[$index] ?? null;
            }

            $this->value = $result;
        }
    }
}