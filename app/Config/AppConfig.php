<?php
declare(strict_types=1);
namespace Jeffrey\Sikapay\Config;

use Dotenv\Dotenv;

class AppConfig
{
    private static ?array $config = null;

    private function __construct() {}

    public static function load(): array
    {
        if (self::$config === null) {
            // Load the .env file from the root directory (../../)
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();

            // Assemble the Configuration Array from Environment Variables
            self::$config = [
                'app' => [
                    'name' => $_ENV['APP_NAME'] ?? 'SikaPay',
                    'env' => $_ENV['APP_ENV'] ?? 'production',
                    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                ],
                'db' => [
                    // Building DSN using the custom DB_PORT
                    'dsn' => $_ENV['DB_DRIVER'] 
                             . ':host=' . $_ENV['DB_HOST'] 
                             . ';port=' . ($_ENV['DB_PORT'] ?? '3306') 
                             . ';dbname=' . $_ENV['DB_NAME'],
                             
                    'user' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASS'],
                ],
            ];
        }
        return self::$config;
    }
}