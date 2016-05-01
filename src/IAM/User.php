<?php

namespace LegalThings\IAM;

/**
 * User entity
 */
class User implements Jasny\Auth\User, Jasny\Authz\User
{
    /**
     * @var string
     */
    public $id;
    
    /**
     * @var string
     */
    public $first_name;

    /**
     * @var string
     */
    public $last_name;

    /**
     * @var string
     */
    public $gender;
    
    /**
     * @var string
     */
    public $email;

    /**
     * @var Organization
     */
    public $organization;
    
    /**
     * @var Team[]
     */
    public $team;
    

    /**
     * Get username (is e-mail)
     *
     * @return string
     */
    public function getUsername()
    {
        return isset($this->email) ? $this->email : null;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return isset($this->password) ? $this->password : null;
    }

    /**
     * Get authorization group
     *
     * @return string
     */
    public function getRole()
    {
        return isset($this->organization) && $this->organization->type === 'primary' ? 'admin' : null;
    }

    /**
     * Check if user has a specific role
     *
     * @param string $role
     * @return boolean
     */
    public function hasRole($role)
    {
        return $this->getRole() === $role;
    }


    /**
     * Callback for login
     * 
     * @return boolean
     */
    public function onLogin()
    {
        return true;
    }

    /**
     * Callback for logout
     */
    public function onLogout()
    {}

    
    /**
     * Check if the user may access this entity
     * 
     * @param AccessControl $entity
     * @param string        $scope
     * @return boolean
     */
    public function mayAccess(AccessControl $entity, $scope = null)
    {
        return strpos($entity->determineAccess($this, $scope), 'r') !== false;
    }

    /**
     * Check if the user may access this entity
     * 
     * @param AccessControl $entity
     * @param string        $scope
     * @return boolean
     */
    public function mayModify(AccessControl $entity, $scope = null)
    {
        return strpos($entity->determineAccess($this, $scope), 'w') !== false;
    }
    
    
    /**
     * Get the full name of the user
     *
     * @return string
     */
    public function getFullname()
    {
        $parts = [];
        if ($this->first_name) $parts[] = $this->first_name;
        if ($this->last_name) $parts[] = $this->last_name;

        return join(' ', $parts);
    }
    
    /**
     * Cast user to string
     *
     * @return string
     */
    public function __toString()
    {
        $parts = [];
        if ($this->last_name) $parts[] = $this->last_name;
        if ($this->first_name) $parts[] = $this->first_name;

        return join(', ', $parts);
    }
    
    /**
     * Cast object to JSON
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $values = [];
        
        foreach ((array)$this as $key=>$value) {
            if ($key[0] === "\0") continue; // Ignore private and protected properties
            $values[$key] = $value;
        }
        
        $values['name'] = $this->getFullname();
        
        return (object)$values;
    }
}
