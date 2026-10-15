<?php
namespace Clicalmani\Validation;

/**
 * InputParser class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
class InputParser
{
    public function parseInt(mixed $value) : int
    {
        if (is_int($value)) return $value;
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Value must be numeric to parse as integer.");
        }
        return (int) $value;
    }

    public function parseFloat(mixed $value) : float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Value must be numeric to parse as float.");
        }
        return (float) $value;
    }

    public function parseBoolean(mixed $value) : bool
    {
        if (is_bool($value)) return $value;
        if ($value === null) return false;

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            throw new \InvalidArgumentException("Value must be boolean or boolean-like to parse as boolean.");
        }
        return $bool;
    }

    public function parseString(mixed $value) : string
    {
        if ($value === null) return '';

        if (!is_scalar($value) && !$value instanceof \Stringable) {
            throw new \InvalidArgumentException("Value must be scalar or string to parse as string.");
        }
        return (string) $value;
    }

    public function parseArray(mixed $value) : array
    {
        if (is_array($value)) return $value;
        if ($value === null)  return [];

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }

        return (array) $value;
    }

    public function parseObject(mixed $value) : \stdClass
    {
        if ($value instanceof \stdClass) return $value;
        if ($value === null) return new \stdClass();

        if (is_string($value)) {
            $decoded = json_decode($value);
            return (json_last_error() === JSON_ERROR_NONE && is_object($decoded)) ? $decoded : new \stdClass();
        }

        return (object) $value;
    }

    public function cast(mixed $value, string $type) : mixed
    {
        return match ($type) {
            'int', 'integer'  => $this->parseInt($value),
            'float', 'double' => $this->parseFloat($value),
            'bool', 'boolean' => $this->parseBoolean($value),
            'array'           => $this->parseArray($value),
            'object'          => $this->parseObject($value),
            default           => $this->parseString($value),
        };
    }
}
