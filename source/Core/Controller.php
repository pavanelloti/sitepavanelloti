<?php

namespace Source\Core;

use Source\Core\View;
use Source\Support\Seo;

class Controller
{
    protected $view;
    protected $seo;

    public function __construct(string $pathToViews = null)
    {

        $this->view = new View($pathToViews);
        $this->seo = new Seo();

    }



}