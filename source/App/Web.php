<?php

namespace Source\App;

use Source\Core\Controller;

class Web extends Controller
{
    public function __construct()
    {

        parent::__construct(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/");

    }

    public function home(): void
    {
        echo "<h1>Home</h1>";
    }

    public function about(): void
    {
        echo "<h1>Sobre</h1>";
    }
    
    public function error(array $data): void
    {
        echo "<h1>Error</h1>";
        var_dump($data);
    }

}