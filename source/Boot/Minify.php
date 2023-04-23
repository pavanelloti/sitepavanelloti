<?php

use MatthiasMullie\Minify;

if(strpos(url(), "localhost")){  //STRPOS - (se dentro da 'variavel/func', existe a palavra "localhost")->:bool
    
    ###########
    /** CSS **/
    ###########
    $minCSS = new Minify\CSS();
    $minCSS->add(__DIR__ . "/../../shared/styles/styles.css");
    $minCSS->add(__DIR__ . "/../../shared/styles/boot.css");

    //theme CSS

    $cssDir = scandir(__DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/assets/css");
    foreach ($cssDir as $value) {
        $cssFile = __DIR__ . "/../../themes/" . CONF_VIEW_THEME . "/assets/css/{$value}";
        if(is_file($cssFile) && pathinfo("$cssFile")['extension'] === "css"){
            $minCSS->add($cssFile);
        }    
    }

    //Minify CSS

    $minifiedPathCss = "/../../themes/" . CONF_VIEW_THEME . "/assets/style.css";
    $minCSS->minify(__DIR__ . $minifiedPathCss);
    

    ##########
    /** JS **/
    ##########

    $minJS = new Minify\JS();
    $minJS->add(__DIR__ . "/../../shared/scripts/jquery.min.js");
    $minJS->add(__DIR__ . "/../../shared/scripts/jquery-ui.js");

    // Theme JS

    $jsDir = scandir(__DIR__ . "/../../themes/". CONF_VIEW_THEME . "/assets/js");
    foreach ($jsDir as $value) {
        $jsFile = __DIR__ . "/../../themes/". CONF_VIEW_THEME . "/assets/js/{$value}";
        if(is_file($jsFile) && pathinfo($jsFile)['extension'] === "js"){
           $minJS->add($jsFile);
        }
        
    }
    
    //Minify JS

    $minifiefPathJS = "/../../themes/" . CONF_VIEW_THEME . "/assets/script.js";
    $minJS->minify(__DIR__ . $minifiefPathJS);

}