<?php
declare(strict_types=1);
namespace Jeffrey\Sikapay\Config;

use Dotenv\Dotenv;
use \Exception; // Use standard Exception for config validation failures

class AppConfig
{
    private static ?array $config = null;

    private function __construct() {}

    /**
     * @return array The immutable application configuration array.
     * @throws Exception If a critical environment variable is missing.
     */
    public static function load(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        // --- 1. Load Environment Variables ---
        try {
            // Use safeLoad() to avoid throwing exceptions if .env is missing,
            // but we must validate critical vars below.
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();
        } catch (\Throwable $e) {
            // Catch any unexpected error during file loading/parsing itself
            throw new Exception("CRITICAL: Failed to load .env file. Check file path and permissions: " . $e->getMessage());
        }

        // --- 2. Define Required Variables and their purpose ---
        $requiredVars = [
            'DB_DRIVER' => 'Database connection driver (e.g., mysql, pgsql)',
            'DB_HOST' => 'Database host address',
            'DB_NAME' => 'Database name',
            'DB_USER' => 'Database username',
            'DB_PASS' => 'Database password',
            // APP_ENV is critical for logging level/debug mode
            'APP_ENV' => 'Application environment (e.g., production, development)', 
        ];

        // --- 3. Strict Validation and Error Handling ---
        foreach ($requiredVars as $var => $description) {
            
            $isMissing = !isset($_ENV[$var]);
            $isEmpty = trim((string)($_ENV[$var] ?? '')) === ''; // Check if the trimmed value is empty
            
            // 🚨 Allow DB_PASS to be empty.
            // If the variable is missing, OR if it's empty AND NOT the DB_PASS, throw an exception.
            if ($isMissing || ($isEmpty && $var !== 'DB_PASS')) {
                throw new Exception("CRITICAL CONFIGURATION ERROR: The required environment variable '{$var}' is missing or empty. ({$description})");
            }
        }
        
        // --- 4. Assemble the Configuration Array ---
        // We now safely assume all required DB vars exist and are non-empty.
        self::$config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'SikaPay',
                // Use the validated APP_ENV
                'env' => (string)$_ENV['APP_ENV'], 
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
            ],
            'db' => [
                // Building DSN using the custom DB_PORT
                'dsn' => (string)$_ENV['DB_DRIVER'] 
                             . ':host=' . $_ENV['DB_HOST'] 
                             . ';port=' . ($_ENV['DB_PORT'] ?? '3306') 
                             . ';dbname=' . $_ENV['DB_NAME'],
                             
                'user' => (string)$_ENV['DB_USER'],
                'password' => (string)$_ENV['DB_PASS'],
                // Add PDO options for connection hardening
                'options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // Most important: Throw exceptions on errors
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC, // Standard fetch mode
                    \PDO::ATTR_EMULATE_PREPARES => false, // For better security and performance
                    \PDO::ATTR_TIMEOUT => 5, // Connection timeout in seconds
                ]
            ],
            // 🚨 NEW: Define other security-relevant settings here
            'security' => [
                'session_lifetime' => 7200, // 2 hours in seconds
                'csrf_protection' => true,
            ]
        ];

        return self::$config;
    }
    
    /**
     * Simple getter for accessing configuration values.
     * @param string $key The configuration key (e.g., 'db.dsn' or 'app.env').
     * @return mixed
     * @throws Exception If the key is not found.
     */
    public static function get(string $key): mixed
    {
        if (self::$config === null) {
            self::load();
        }
        
        $parts = explode('.', $key);
        $value = self::$config;
        
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                throw new Exception("Configuration key '{$key}' not found.");
            }
            $value = $value[$part];
        }
        
        return $value;
    }
}