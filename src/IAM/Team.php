<?php

use Jasny\DB\Mongo\Sorted;
use Jasny\DB\Mongo\Document\AutoSorting;
use Jasny\DB\Mongo\Document\SoftDeletion;
use Jasny\DB\Mongo\Document\DeletionFlag;

/**
 * Team entity
 */
class Team extends MongoDocument implements SoftDeletion, AccessControl, Sorted
{
    use AutoSorting;
    use DeletionFlag;

    /**
     * Collection name
     * @var string
     */
    protected static $collection = 'teams';

    /**
     * Fields to search on (poor man's search)
     * @var string
     */
    protected static $searchFields = [
        'name'
    ];

    
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     * @options primary, secondary
     * @required
     * @immutable
     */
    public $type = 'primary';

    /**
     * @var Organization
     * @immutable
     * 
     * Either an organzation or project should be set
     */
    public $organization;

    /**
     * @var Project
     * @immutable
     * 
     * Either an organzation or project should be set
     */
    public $project;

    /**
     * Sort field
     * @var string
     */
    public $_sort;

    /**
     * Users, including user's role
     * @var User[]
     * @dbValue { _id:(MongoID)id, role, leader, contact }
     */
    public $users = [];

    
    /**
     * Get field map
     *
     * @return array
     */
    protected static function getFieldMap()
    {
        return [
            'organization_id' => 'organization',
            'project_id' => 'project'
        ];
    }
    
    /**
     * Get data to save
     *
     * @return $this
     */
    protected function toData()
    {
        $data = parent::toData();

        foreach ($data['users'] as &$user) {
            $user = (object)[
                '_id' => $user->_id,
                'role' => isset($user->role) ? $user->role : null,
                'leader' => isset($user->leader) ? $user->leader : false,
                'contact' => isset($user->contact) ? $user->contact : false,
            ];
        }

        return $data;
    }

    /**
     * Convert a filter to a Mongo query
     * 
     * @param array $filter
     * @return array
     */
    protected static function filterToQuery($filter)
    {
        $query = parent::filterToQuery($filter);
        
        if (isset($filter['users'])) $query['users'] = ['$elemMatch' => ['_id' => $query['users']]];
        
        return $query;
    }    
    
    /**
     * Class constructor
     * 
     * @param Organization|Project $parent
     */
    public function __construct($parent = null)
    {
        if (!isset($this->organization) && !isset($this->project)) {
            if ($parent instanceof Organization) $this->organization = $parent;
            if ($parent instanceof Project) $this->project = $parent;
        }
        
        parent::__construct();
    }
    
    /**
     * Get all team members
     * 
     * @return User[]
     */
    public function getUsers()
    {
        $users = array();

        foreach ($this->users as $i => $user) {
            if (!isset($user->last_name)) continue;
            $users[] =  $user;
        }

        return $users;
    }
    
    /**
     * Find a member 
     * 
     * @param User|string $user
     * @return User
     */
    public function getUser($user)
    {
        $id = $user instanceof User ? $user->getId() : $user;
        
        foreach ($this->getUsers() as $member) {
            if ($member->getId() === $id) return $member;
        }
        
        return null;
    }

    /**
     * Add a team member or change member's role
     * 
     * @param User  $user
     * @return User
     */
    public function addUser(User $user)
    {
        $member = $this->getUser($user);
        
        if (!$member) {
            $member = clone $user;
            $this->users[] = $member;
        }
        
        return $member;
    }
    
    /**
     * Remove a team member
     * 
     * @param User|string $user
     */
    public function removeUser(User $user)
    {
        $id = $user instanceof User ? $user->getId() : $user;
        
        foreach ($this->users as $i => $member) {
            if ($member->getId() === $id) {
                unset($this->users[$i]);
                break;
            }
        }
    }
    
    
    /**
     * Cast entity to string
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * Get team as list item
     * 
     * @return object
     */
    public function asListItem()
    {
        $item = parent::asListItem();
        
        $item->organization = $this->organization->asListItem();
        if (isset($this->type)) $item->type = $this->type;
        
        return $item;
    }
    
    /**
     * Cast object to JSON
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $object = parent::jsonSerialize();

        if (is_null($object->organization)) unset($object->organization);
        if (is_null($object->project)) unset($object->project);

        foreach ($object->users as $i => $user) {
            if (!isset($user->last_name)) unset($object->users[$i]);
        }
        
        return $object;
    }
    
    /**
     * Check if the user may read and/or write this team
     * 
     * @param User|null $user
     * @param string    $scope
     * @return string 'r' or 'rw'
     */
    public function determineAccess($user, $scope = null)
    {
        if (!$user) return '';
        
        if ($user->hasRole('admin') || $this->organization->_id == $user->organization->_id) return 'rw';
        if ($this->organization->type === 'primary') return 'r';
        return '';
    }
}
