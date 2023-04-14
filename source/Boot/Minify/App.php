<?php 

use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\CSS;

if(strpos(url(), "localhost")) {
    /**
     * CSS
     */
    $minCss = new CSS();
    $minCss->add(__DIR__ . "/../../../shared/styles/styles.css");
    $minCss->add(__DIR__ . "/../../../shared/styles/boot.css");

    // Theme CSS
    $cssDir = scandir(__DIR__ . "/../../../themes/". CONF_VIEW_APP ."/assets/css");
    foreach($cssDir as $css) {
        $cssFile = __DIR__ . "/../../../themes/". CONF_VIEW_APP ."/assets/css/{$css}";
        if(is_file($cssFile) && pathinfo($cssFile)['extension'] == "css") {
            $minCss->add($cssFile);
        }
    }

    // Minify Css
    $minCss->minify(__DIR__ . "/../../../themes/". CONF_VIEW_APP ."/assets/style.css");

    /**
    * JS
    */
    $minJS = new JS();
    $minJS->add(__DIR__ . "/../../../shared/scripts/jquery.min.js");
    $minJS->add(__DIR__ . "/../../../shared/scripts/jquery.form.js");
    $minJS->add(__DIR__ . "/../../../shared/scripts/jquery-ui.js");

    // Theme JS
    $jsDir = scandir(__DIR__ . "/../../../themes/". CONF_VIEW_APP ."/assets/js");
    foreach($jsDir as $js) {
        $jsFile = __DIR__ . "/../../../themes/". CONF_VIEW_APP ."/assets/js/{$js}";
        if(is_file($jsFile) && pathinfo($jsFile)['extension'] == "js") {
            $minJS->add($jsFile);
        }
    }

    // Minify Js
    $minJS->minify(__DIR__ . "/../../../themes/". CONF_VIEW_APP . "/assets/script.js");
}