<?php
namespace Demo\IndexBundle\Model;

use Demo\IndexBundle\Model\ArticleTag;

final class Tag extends \Sybil\ORM\DBMS\Model
{
    protected $entity_name = 'tag';
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
     * Objects methods.
     */

    public function getArticles($params=null)
    {
        $article_tag = new ArticleTag();
        return $article_tag->match(['tag' => $this->id])->params($params)->execute();
    }

    public function getUsers($params=null)
    {
        $article_tag = new ArticleTag();
        return $article_tag->match(['tag' => $this->id])->params($params)->execute();
    }

    /*
     * Custom methods.
     */
}
