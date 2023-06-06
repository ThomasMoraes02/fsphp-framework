<?php 
namespace Source\Models;

use Source\Core\Model;

class Post extends Model
{
    public function __construct()
    {
        parent::__construct("posts", ["id"], ["title", "id", "subtitle", "content"]);
    }

    public function findPost(?string $terms = null, ?string $params = null, string $columns = "*")
    {
        if(!$this->all) {
            $terms = "status = :status AND post_at <= NOW()" . ($terms ? " AND {$terms}" : "");
            $params = "status=post" . ($params ? "&{$params}" : "");
        }

        return parent::find($terms, $params, $columns);   
    }

    public function findByUri(string $uri, string $columns = "*"): ?Post
    {
        $find = $this->find("uri = :uri", "uri={$uri}", $columns);
        return $find->fetch();
    }

    public function author(): ?User
    {
        if($this->author) {
            return (new User)->findById($this->author);
        }

        return null;
    }

    public function category()
    {
        if($this->category) {
            return (new Category)->findById($this->category);
        }

        return null;
    }
}