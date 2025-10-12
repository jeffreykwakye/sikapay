<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function connect(array $config): PDO
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    $config['dsn'], 
                    $config['user'], 
                    $config['password'], 
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    ]
                );
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    public static function getInstance(): ?PDO
    {
        return self::$instance;
    }
}