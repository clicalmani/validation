<?php
namespace Clicalmani\Validation;

trait InputParser
{
    /**
     * Parse int
     * 
     * @param mixed $value
     * @return int Parsed value
     */
    public function parseInt(mixed $value) : int
    {
        return (int) tap($value, fn($value) => settype($value, 'integer'));
    }

    /**
     * Parse float
     * 
     * @param mixed $value
     * @return float Parsed value
     */
    public function parseFloat(mixed $value) : float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("Value must be numeric to parse as float.");
        }
        
        return (float) tap($value, fn($value) => settype($value, 'double'));
    }

    /**
     * Parse boolean
     * 
     * @param mixed $value
     * @return bool Parsed value
     */
    public function parseBoolean(mixed $value) : bool
    {
        if (!is_bool($value) && !is_numeric($value)) {
            throw new \InvalidArgumentException("Value must be boolean or numeric to parse as boolean.");
        }

        if (is_null($value)) {
            return false; // Default to false if null
        }

        return tap($value, fn($value) => settype($value, 'boolean'));
    }

    /**
     * Parse string
     * 
     * @param mixed $value
     * @return string
     */
    public function parseString(mixed $value) : string
    {
        if (is_null($value)) {
            return ''; // Default to empty string if null
        }

        if (!is_scalar($value) && !is_string($value)) {
            throw new \InvalidArgumentException("Value must be scalar or string to parse as string.");
        }

        // Convert to string and ensure type is set correctly
        return tap((string)$value, fn(string $value) => settype($value, 'string'));
    }

    /**
     * Parse array
     * 
     * @param mixed $value
     * @return array
     */
    public function parseArray(mixed $value) : array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_null($value)) {
            return [];
        }
        if (!is_string($value) && !is_object($value)) {
            return (array)$value;
        }
        if (is_string($value)) {
            $value = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
        }
        if (is_object($value)) {
            $value = (array)$value;
        }
        if (!is_array($value)) {
            return [];
        }
        // Ensure the value is an array
        return tap($value, fn(&$value) => settype($value, 'array'));
    }

    /**
     * Parse object
     * 
     * @param mixed $value
     * @return \stdClass
     */
    public function parseObject(mixed $value) : \stdClass
    {
        if (is_object($value)) {
            return $value;
        }

        if (is_null($value)) {
            return new \stdClass(); // Default to empty object if null
        }

        if (is_array($value)) {
            $value = (object)$value; // Convert array to object
        } elseif (is_string($value)) {
            $decoded = json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded; // Use decoded object if valid JSON
            } else {
                $value = new \stdClass(); // Default to empty object if JSON is invalid
            }
        } elseif (!is_object($value)) {
            $value = (object)$value; // Convert other types to object
        }

        if (!is_object($value)) {
            throw new \InvalidArgumentException("Value must be an object, array, or string to parse as object.");
        }
        
        // Ensure the value is an object
        return tap($value, fn($value) => settype($value, 'object'));
    }
}
