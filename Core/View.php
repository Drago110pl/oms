<?php

namespace Core;
use Core\Controller;
use Core\Router;
use Core\Error;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\StringLoaderExtension;

use Core\Config\Config;

class View  
{
    private static $twig;
    private static $app;
    /**
     * Renderuje szablon z danymi.
     *
     * @param string $template Nazwa szablonu (plik PHP w katalogu Views)
     * @param array $data Dane do wstawienia w szablonie
     */
    public function __construct($app)
    {
      
        self::$app = $app;
    }

    public static function addGlobal($name, $value)
    {
        if (!isset(self::$twig)) {
            $loader = new FilesystemLoader(__DIR__ . '/../App/Views');
          
            self::$twig = new Environment($loader);
            self::$twig->addExtension(new StringLoaderExtension());
        }

        self::$twig->addGlobal($name, $value);
    }

    public static function render($view, $data = [], $template = null)
    {
       
        View::addGlobal('app', self::$app);
      
        if (!isset(self::$twig)) {
            $loader = new FilesystemLoader(__DIR__ . '/../App/Views');
          
            self::$twig = new Environment($loader);
            self::$twig->addExtension(new StringLoaderExtension());
        }

        $Layout = Config::get('website')['theme'];
        $theme =  View::getTemplatePath($Layout);
        
        $data['view_directory'] = $view;
        
        echo self::$twig->render(View::setTheme($template, $theme), $data);

        
    } 



    public static function setTheme($template, $theme) {
         
        $conf = self::$app['config']['template'];

        if($template == null) {
            return $theme['src'];
        } else { 

            if($conf != null) {
                return $theme['templates'][$conf];
            } else {
                return $theme['src'];
            
            }

        }

    }

    public static function getTemplatePath($id) {

        $errorsData = json_decode(file_get_contents(__DIR__ . '/Config/Template.json'), true);
       
        return $errorsData[$id];
    
    }

   

   
   
}