<?php

namespace Core\Config;

class Config
{
    private static $config = [
        'mail' => [
            'host' => '',
            'username' => '',
            'password' => '',
            'smtp_secure' => '',
            'port' => 587,
            'from_email' => 'your@example.com',
            'from_name' => 'Your Name'
        ],
        'database' => [
            'host' => '127.0.0.1',
            'username' => '',
            'password' => '',
            'name' => '',
        ],
        'website' => [
            'title' => '',
            'theme' => 'default',
            'url' => 'http://localhost/oms'
        ]
        
        // inne ustawienia konfiguracyjne...
    ];

    public static function get($key)
    {
      
     
        // Sprawdź, czy istnieje klucz w tablicy konfiguracyjnej
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        } else {
            return null; // Możesz obsłużyć ten przypadek w zależności od potrzeb projektu
        }
    }
}
