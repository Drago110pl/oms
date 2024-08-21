<?php

namespace Core;

use Core\Database;
use Core\Config\Config;

class Model {
    protected $db;

    public function __construct() {
       
        // Utwórz połączenie z bazą danych
        $this->db = new Database(
            Config::get('database')['host'], 
            Config::get('database')['username'], 
            Config::get('database')['password'],
            Config::get('database')['name'], 
        );
    }
    
    

    public function __destruct() {
        // Zamknij połączenie z bazą danych przy zakończeniu działania skryptu
        $this->db->closeConnection();
    }
}
