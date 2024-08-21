<?php

namespace App\Models;

use Core\Model;
use Core\Session;

class User extends Model {
 
    public function login($email, $password) {
      
        // Sprawdzenie czy użytkownik istnieje w bazie danych
        $query = "SELECT * FROM users WHERE email = :email AND password = :password";
        $params = array(':email' => $email, ':password' => md5($password));
        $user = $this->db->fetch($query, $params); 
       
        // Jeśli użytkownik istnieje
        if ($user) { 
            // Zapisanie ID użytkownika w sesji
            Session::set('user_id', $user['id']);
            
            return true;
        } else {
            return false;
        }
    }

    public static function logout() {
        // Usunięcie ID użytkownika z sesji
        Session::delete('user_id');
    }


    
}