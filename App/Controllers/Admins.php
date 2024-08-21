<?php

namespace App\Controllers;

use Core\Controller;
use Core\View;
use Core\Session;
use Core\Error;
use App\Models\User;
use Core\Router; 

class Admins extends Controller {

    
    public function index($id)
    {
        $userId = Session::get('user_id');
       
        if (isset($userId)) {  
            View::render('Admin/Home.html', [], "admin");
        } else { 
            if (!empty($_POST)) {
                $User = new User();
                $loggedIn = $User->login($_POST['email'], $_POST['password']);
            }
            View::render('User/Login.html', []);
        }
    }
}
?>
