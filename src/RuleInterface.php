<?php
namespace Clicalmani\Validation;

interface RuleInterface
{
    /**
     * Rule validator
     * 
     * @param mixed &$value Input value
     * @return bool
     */
    public function validate(mixed &$value) : bool;

    /**
     * Rule options
     * 
     * @return array
     */
    public function options() : array;

    /**
     * Gets the custom error message.
     * 
     * @return string
     */
    public function message() : ?string;

    /**
     * Input value is required
     * 
     * @return bool
     */
    public function isRequired() : bool;

    /**
     * Input value is nullable
     * 
     * @return bool
     */
    public function isNullable() : bool;

    /**
     * Input value is sometimes required
     * 
     * @return bool
     */
    public function isSometimes() : bool;

    /**
     * Input value is hash
     * 
     * @return bool
     */
    public function isHash() : bool;

    /**
     * Input value is confirmed
     * 
     * @return bool
     */
    public function isConfirmed() : bool;

    /**
     * Check rule options
     * 
     * @return bool
     */
    public function checkOptions() : bool;

    /**
     * Gets the rule argument.
     * 
     * @return string
     */
    public static function getArgument() : string;

    /**
     * Log validation error.
     * 
     * @param ?string $message
     * @return void
     */
    public function log(?string $message = null) : void;
}