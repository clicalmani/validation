<?php
namespace Clicalmani\Validation;

use Clicalmani\Validation\Exceptions\ValidationException;

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
        private mixed $default = null
    )
    {
        // ...
    }

    public function validate(): void
    {
        if (!!$this->is_required && !$this->value) {
            throw new ValidationException(sprintf("Option %s is required for %s rule.", $this->name));
        }
        
        if ($this->func && is_callable($this->func)) {
            $this->value = call_user_func($this->func, $this->value);
        }
        
        if ($this->validator && is_callable($this->validator) && FALSE == call_user_func($this->validator, $this->value)) {
            throw new ValidationException("$this->value is not a valid option $this->name value for %s rule.");
        }
        
        if ($this->type) {
            $this->value = (new InputParser())->cast($this->value, $this->type);
        }
        
        if ($this->keys) {
            $result = [];

            foreach ($this->keys as $index => $key) {
                $result[$key] = $this->value[$index];
            }

            $this->value = $result;
        }

        $this->value ??= $default;
    }
}