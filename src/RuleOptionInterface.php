<?php
namespace Clicalmani\Validation;

/**
 * RuleOptionInterface interface
 * 
 * @package Clicalmani\Validation
 * @author  Clément Clicalmani <
 */
interface RuleOptionInterface
{
    /**
     * Is valid
     * 
     * @return void
     */
    public function validate() : void;
}