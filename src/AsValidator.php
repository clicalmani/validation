<?php
namespace Clicalmani\Validation;

/**
 * Service tag to autoconfigure validators.
 * 
 * AsValidator class
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class AsValidator
{
    public mixed $args;

    public function __construct(mixed ...$args)
    {
        $this->args = $args;
    }
}
