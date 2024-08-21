<?php

namespace Core;

use Core\View;
use Core\Router;
use Core\Error;

class Controller extends Router {

    public $view;
   
    public function __construct($app)
    {
        $this->app = $app; // Initialize the inherited property

        new View($app); 
       
        
    }
    
    

    public function redirect($url) {
        header("Location: $url");
    }

    

}