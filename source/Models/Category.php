<?php 
namespace Source\Models;

use Source\Core\Model;

class Category extends Model
{
    public function __construct()
    {
        parent::__construct("categories", ["id"], ["title", "description"]);
    }

    /**
     * @param string $uri
     * @param string $columns
     * @return Category|null
     */
    public function findByUri(string $uri, string $columns = "*"): ?Category
    {
        $find = $this->find("uri = :uri", "uri={$uri}", $columns);
        return $find->fetch();
    }

    /**
     * @return Post
     */
    public function posts(): Post
    {
        return (new Post)->find("category = :id", "id={$this->id}");
    }

    /**
     * @return boolean
     */
    public function save(): bool
    {
        $checkUri = (new Category)->find("uri = :uri AND id != :id", "uri={$this->uri}&id={$this->id}");

        if($checkUri->count()) {
            $this->uri = "{$this->uri}-{$this->lastId()}";
        }

        return parent::save();
    }
}