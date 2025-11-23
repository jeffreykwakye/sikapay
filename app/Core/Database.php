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
    private static array $config = [];

    private function __construct() {}
    private function __clone() {}

    /**
     * Establishes the database connection using the Singleton pattern with retry logic.
     * @param array $config Database configuration array.
     * @return PDO The PDO instance.
     */
    public static function connect(array $config): PDO
    {
        self::$config = $config;

        if (self::$instance === null) {
            $maxRetries = 3;
            $retryDelay = 2; // seconds
            $exception = null;

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    self::$instance = new PDO(
                        $config['dsn'],
                        $config['user'],
                        $config['password'],
                        $config['options'] ?? []
                    );
                    // On success, return the instance immediately
                    return self::$instance;
                } catch (PDOException $e) {
                    $exception = $e;
                    Log::warning("Database connection attempt #{$attempt} of {$maxRetries} failed.", [
                        'error' => $e->getMessage()
                    ]);
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                    }
                }
            }

            // If loop finishes, all retries have failed.
            Log::critical("Database Connection Failed after {$maxRetries} attempts: " . $exception->getMessage(), [
                'dsn_prefix' => substr($config['dsn'], 0, strpos($config['dsn'], ':')),
                'database_user' => $config['user']
            ]);

            if (class_exists(ErrorResponder::class)) {
                ErrorResponder::respond(500, "The application is currently unavailable due to a critical database error.");
            } else {
                http_response_code(500);
                die("<h1>500 Internal Server Error</h1><p>The application is unavailable due to a database connection failure after multiple retries.</p>");
            }
        }
        return self::$instance;
    }


    /**
     * Retrieves the database connection instance, ensuring it is active.
     * If the connection is lost, it attempts to reconnect.
     * @return PDO The active PDO instance.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                throw new PDOException("Database is not configured. Cannot create connection.");
            }
            return self::connect(self::$config);
        }

        try {
            // A lightweight query to check if the connection is still alive.
            self::$instance->query('SELECT 1');
        } catch (PDOException $e) {
            // Error codes for "server has gone away" vary between drivers.
            // 2006: MySQL server has gone away
            // 2013: Lost connection to MySQL server during query
            if (in_array($e->errorInfo[1] ?? null, [2006, 2013]) || str_contains(strtolower($e->getMessage()), 'server has gone away')) {
                Log::info("Database connection lost. Attempting to reconnect.", ['error' => $e->getMessage()]);
                self::$instance = null;
                return self::connect(self::$config);
            }
            
            // Re-throw other exceptions that are not related to connection loss.
            throw $e;
        }

        return self::$instance;
    }
}