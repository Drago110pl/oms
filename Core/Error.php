<?php

namespace Core;

use Core\View;

class Error extends Router
{
 
    public static function render($id)
    { 
        
        // Ładowanie danych błędów z pliku JSON
        $errorsData = json_decode(file_get_contents(__DIR__ . '/Config/Errors.json'), true);
        // Sprawdzenie, czy istnieje wpis dla danego ID
        $data['header'] = $errorsData[$id]['header'];
        $data['descr'] = $errorsData[$id]['description'];
        
        // Renderowanie szablonu błędu przy użyciu Twig
        View::render('Error/errorHandler.html', $data);
      
    }

   

}
?>
