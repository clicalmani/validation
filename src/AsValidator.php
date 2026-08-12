<?php
namespace Clicalmani\Validation;

/**
 * Service tag to autoconfigure validators.
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
