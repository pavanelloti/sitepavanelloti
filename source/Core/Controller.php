<?php

namespace Source\Core;

use Source\Core\View;
use Source\Support\Seo;
use Source\Support\Message;

class Controller
{
    protected $view;
    protected $seo;
    protected $message;

    public function __construct(string $pathToViews = null)
    {

        $this->view = new View($pathToViews);
        $this->seo = new Seo();
        $this->message = new Message();

    }



}