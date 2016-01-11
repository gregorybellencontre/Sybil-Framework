<?php
namespace Demo\IndexBundle\Model;

final class ArticleTag extends \Sybil\ORM\DBMS\Model
{
    protected $entity_name = 'article_tag';
    protected $identifier = ['article','tag','user'];

    protected $article;
    protected $tag;
    protected $user;
    protected $comment;

    /*
     * Getters & setters.
     */

    public function getArticle()
    {
        return $this->article;
    }

    public function setArticle(\Demo\IndexBundle\Model\Article $article)
    {
        $this->article = $article;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag(\Demo\IndexBundle\Model\Tag $tag)
    {
        $this->tag = $tag;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(\Demo\UserBundle\Model\User $user)
    {
        $this->user = $user;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /*
     * Custom methods.
     */
}
