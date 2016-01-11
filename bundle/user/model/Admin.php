<?php
namespace Demo\UserBundle\Model;

final class Admin extends \Sybil\ORM\DBMS\Model
{
    protected $entity_name = 'admin';
    protected $identifier = 'user';

    protected $user;
    protected $secret_key;

    /*
     * Getters & setters.
     */

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(\Demo\UserBundle\Model\User $user)
    {
        $this->user = $user;
    }

    public function getSecretKey()
    {
        return $this->secret_key;
    }

    public function setSecretKey($secret_key)
    {
        $this->secret_key = $secret_key;
    }

    /*
     * Custom methods.
     */
}
