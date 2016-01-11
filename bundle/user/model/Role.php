<?php
namespace Demo\UserBundle\Model;

final class Role extends \Sybil\ORM\DBMS\Model
{
    protected $entity_name = 'user_role';
    protected $identifier = 'id';

    protected $id;
    protected $name;

    /*
     * Getters & setters.
     */

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /*
     * Custom methods.
     */
}
