<?php

namespace App\Controllers;

use Core\Controller;
use Core\View;
use App\Models\Home;

class Homes extends Controller
{
    public function index($id)
    {
        
        $arr = ['test' => 'tekst1', 'test2' => 'tekst2'];

        $test = ['tester' => 150, 'array' => $arr];
    
        View::render('Home/Home.html', $test);
        
        
  

    }
    
}