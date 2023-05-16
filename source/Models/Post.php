<?php

namespace Source\Models;

use Source\Core\Model;

class Post extends Model 
{

    private $all;

    public function __construct(bool $all = false)
    {
        $this->all = $all;
        parent::__construct( "posts", ["id"], ["title", "uri", "subtitles", "content"] );
    }
    
    public function find(?string $terms = null, ?string $params = null, string $columns = "*")
    {
        if (!$this->all) {
            $terms = "status = :status AND post_at <= NOW()" . ($terms ? " AND {$terms}" : "");
            $params = "status=post" . ($params ? " &{$params}" : "");
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
        if ($this->author){
            return (new User())->findById($this->author);
        }
    }
    
    public function category(): ?Category
    {
        if ($this->category){
            return (new Category())->findById($this->category);
        }
    }
       
}
