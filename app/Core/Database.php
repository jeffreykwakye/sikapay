<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use PDO;
use PDOException;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    /**
     * Establishes the database connection using the Singleton pattern.
     * @param array $config Database configuration array.
     * @return PDO The PDO instance.
     */
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
                
                // 1. Log the critical connection failure
                Log::critical("Database Connection Failed: " . $e->getMessage(), [
                    'dsn_prefix' => substr($config['dsn'], 0, strpos($config['dsn'], ':')),
                    'database_user' => $config['user']
                ]);
                
                // 2. Halt execution and display a generic 500 server error
                // We must use a separate instance check for ErrorResponder, 
                // in case the database connection is required *before* the Autoloader can fully load everything.
                if (class_exists(ErrorResponder::class)) {
                    ErrorResponder::respond(500, "The application is currently unavailable due to a critical database error.");
                } else {
                    // Fallback in case ErrorResponder itself cannot be loaded (highly unlikely but safer)
                    http_response_code(500);
                    die("<h1>500 Internal Server Error</h1><p>The application is unavailable due to a database connection failure.</p>");
                }
            }
        }
        return self::$instance;
    }

    /**
     * Retrieves the database connection instance.
     * @return ?PDO The PDO instance or null if not yet connected.
     */
    public static function getInstance(): ?PDO
    {
        return self::$instance;
    }
}