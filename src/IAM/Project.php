<?php

namespace LegalThings\IAM;

/**
 * Project entity
 */
class Project
{
    /**
     * @var string
     */
    public $id;
    
    /**
     * Project name
     * @var string
     */
    public $name;

    /**
     * Project organization
     * @var Organization
     */
    public $organization;

    
    /**
     * Cast entity to string
     * 
     * @return string
     */
    public function __toString()
    {
        return isset($this->name) ? $this->name : '';
    }
}
