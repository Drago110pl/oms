<?php

namespace Core;

use Core\Session;
use Core\Error;
use Core\View;
use Core\Config\Config;
use App\Models\User;

class Router
{
    protected $routes = [];
    private $routes2 = [];
    public $app;
    protected static $staticApp;  // Zmienna statyczna przechowująca instancję
   
    public function __construct()
    {
       
        Session::init();
    
    }
    public function addRoute($pattern, $callback) {
        $this->routes[$pattern] = $callback;
    }
    public function loadRoutesFromJson($file)
    {
        $json = file_get_contents($file);
        $this->routes = json_decode($json, true);
    }
    public function route() {
        $this->loadRoutesFromJson(__DIR__ . '/Config/Router.json');

            $path = $this->getCurrentUrl();
            $urlParts = explode('/', trim($path, '/'));
            $url = Config::get('website')['url'];
            
            $this->setApp([
                'url' => $url,
                'path' => $path,
            ]);
 
            // Iterate through each part of the URL
            for ($i = count($urlParts); $i > 0; $i--) {
                $currentUrl = "/".implode('/', array_slice($urlParts, 0, $i));
                
                if (isset($this->routes[$currentUrl])) {
                    // If the current URL part matches a route, call the associated callback
                    $params = array_slice($urlParts, $i);

                    if(!empty($_GET)) {
                        $params = array_merge($params, $_GET); 
                    }

                    $this->setApp([
                        'url' => $url,
                        'path' => $path,
                        'get' => $params,
                        'config' => $this->routes[$currentUrl]
                    ]);
                    
                    $this->handleRoute($this->routes[$currentUrl], $params, $path);
                   
                    return;
                }
            }
    
            // If no route matches, handle accordingly
            echo "404 Not Found";
        
    }
    public function route2()
    {
      
        $this->loadRoutesFromJson(__DIR__ . '/Config/Router.json');
        $path = $this->getCurrentUrl();
        $url = Config::get('website')['url'];

        $this->setApp([
            'url' => $url,
            'path' => $path,
        ]);

        foreach ($this->routes as $route => $config) {
            if ($this->matchRoute($path, $route, $matches)) {

                $this->setApp([
                    'url' => $url,
                    'path' => $path,
                    'get' => $matches,
                    'config' => $config
                ]);

                $this->handleRoute($config, $matches, $route);
                return;
            }
        }
        
        Error::render(404);

        

    }

    public function setApp($app) 
    {
        $this->app = $app;
        self::$staticApp = $app;
    }

    public function getCurrentUrl()
    {
        $currentUrl = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
        $currentDirectory = dirname($_SERVER['SCRIPT_NAME']);
        $currentUrl = str_replace($currentDirectory, '', $currentUrl);
        return $currentUrl;
    }

    public function matchRoute($url, $route, &$matches = [])
    {
      
        $routePattern = $this->buildRoutePattern($route);
        if (preg_match($routePattern, $url, $params)) {
            $matches = $params;
            
            return true;
        }
        return false;
    }

    public function buildRoutePattern($route)
    {
         // Replace all placeholders (e.g., :param) with a pattern to match any characters
    $pattern = preg_replace('/:[^\/]+/', '[^\/]+', $route);
    // Escape slashes in the pattern
    $pattern = str_replace('/', '\/', $pattern);
    // Add regex to match parameters in the URL with unlimited occurrences
    $pattern .= '(\/\d+)*';

    return '/^' . $pattern . '$/';
    }

    public function handleRoute($config, $matches, $route)
    {

        $controllerName = 'App\\Controllers\\' . $config['controller'];
        $action = $config['action'];

        if (class_exists($controllerName)) {
            $controller = new $controllerName($this->app); // Pass the det value to the constructor
            if (method_exists($controller, $action)) {
               
                $controller->$action($matches);
              
                return; 
            } else {
                Error::render(1090);
                return;
            }
        } else {
            Error::render(1080);
            return;
        }

      
    }
}









