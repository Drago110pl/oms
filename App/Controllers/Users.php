<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;
use Core\Error;

class Users extends Controller {

    public function Logout() {
        // Przykładowe zapytanie

        User::logout();
        Error::render(920);

    }

}

?>