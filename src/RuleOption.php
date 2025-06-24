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
        private array $keys = [],
        private ?\Closure $func = null,
        private ?\Closure $validator = null
    )
    {
        // ...
    }

    public function validate(): void
    {
        if (!!$this->is_required && !$this->value) {
            throw new ValidationException(sprintf("Option %s is required for %s rule.", $this->name));
        }
        
        if ($this->func && $this->func instanceof \Closure) {
            $this->value = $this->func->call($this, $this->value);
        }
        
        if ($this->validator && FALSE == call($this->validator, $this->value)) {
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
    }
}