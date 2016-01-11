<?php
namespace Demo\UserBundle\Model;

use Sybil\App;
use Demo\IndexBundle\Model\ArticleTag;

final class User extends \Sybil\ORM\DBMS\Model
{
    protected $entity_name = 'user';
    protected $identifier = 'id';

    protected $id;
    protected $login;
    protected $password;
    protected $note;
    protected $role;

    /*
     * Getters & setters.
     */

    public function getId()
    {
        return $this->id;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = App::hash($password);
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote($note)
    {
        $this->note = $note;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole(\Demo\UserBundle\Model\Role $role)
    {
        $this->role = $role;
    }

    /*
     * Objects methods.
     */

    public function getArticles($params=null)
    {
        $article_tag = new ArticleTag();
        return $article_tag->match(['user' => $this->id])->params($params)->execute();
    }

    public function getTags($params=null)
    {
        $article_tag = new ArticleTag();
        return $article_tag->match(['user' => $this->id])->params($params)->execute();
    }

    /*
     * Custom methods.
     */
}
