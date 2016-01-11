<?php
namespace Demo\IndexBundle\Model;

use Demo\IndexBundle\Model\ArticleTag;

final class Article extends \Sybil\ORM\DBMS\Model
{
    protected $entity_name = 'article';
    protected $identifier = 'id';

    protected $id;
    protected $title;
    protected $date;
    protected $last_update;
    protected $author;

    /*
     * Getters & setters.
     */

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date)
    {
        $this->date = $date;
    }

    public function getLastUpdate()
    {
        return $this->last_update;
    }

    public function setLastUpdate($last_update)
    {
        $this->last_update = $last_update;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(\Demo\UserBundle\Model\User $author)
    {
        $this->author = $author;
    }

    /*
     * Objects methods.
     */

    public function getTags($params=null)
    {
        $article_tag = new ArticleTag();
        return $article_tag->match(['article' => $this->id])->params($params)->execute();
    }

    public function getUsers($params=null)
    {
        $article_tag = new ArticleTag();
        return $article_tag->match(['article' => $this->id])->params($params)->execute();
    }

    /*
     * Custom methods.
     */
}
