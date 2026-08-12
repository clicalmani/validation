<?php
namespace Clicalmani\Validation;

interface RuleOptionInterface
{
    /**
     * Is valid
     * 
     * @return void
     */
    public function validate() : void;
}