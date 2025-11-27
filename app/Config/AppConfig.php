<?php
declare(strict_types=1);
namespace Jeffrey\Sikapay\Config;

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

        // --- 1. Load Configuration File ---
        $configFile = __DIR__ . '/../config.php';
        if (!file_exists($configFile)) {
            throw new Exception("CRITICAL: Configuration file not found at 'app/config.php'. Please copy 'app/config.example.php' to 'app/config.php' and fill in your details.");
        }
        $loadedConfig = require $configFile;

        // --- 2. Define Required Variables and their purpose ---
        // This validation is now against the loaded config array
        $requiredVars = [
            'db.driver' => 'Database connection driver (e.g., mysql, pgsql)',
            'db.host' => 'Database host address',
            'db.name' => 'Database name',
            'db.user' => 'Database username',
            'db.password' => 'Database password (can be empty)',
            'app.env' => 'Application environment (e.g., production, development)',
        ];

        // --- 3. Strict Validation and Error Handling ---
        foreach ($requiredVars as $key => $description) {
            $parts = explode('.', $key);
            $value = $loadedConfig;
            $found = true;
            foreach ($parts as $part) {
                if (!isset($value[$part])) {
                    $found = false;
                    break;
                }
                $value = $value[$part];
            }

            // Allow db.password to be empty string, but it must be set.
            $isMissing = !$found;
            $isEmpty = trim((string)($value ?? '')) === '';

            if ($isMissing || ($isEmpty && $key !== 'db.password')) {
                throw new Exception("CRITICAL CONFIGURATION ERROR: The required config key '{$key}' is missing or empty in 'app/config.php'. ({$description})");
            }
        }

        // --- 4. Assemble the Internal Configuration Array ---
        // This structure should match what the application expects
        self::$config = [
            'app' => [
                'name' => $loadedConfig['app']['name'] ?? 'SikaPay',
                'env' => (string)($loadedConfig['app']['env'] ?? 'production'),
                'url' => $loadedConfig['app']['url'] ?? 'http://localhost',
            ],
            'db' => (function() use ($loadedConfig) {
                $dbConfig = $loadedConfig['db'];

                // Assemble DSN
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s',
                    $dbConfig['driver'],
                    $dbConfig['host'],
                    $dbConfig['port'] ?? '3306',
                    $dbConfig['name']
                );

                // Base PDO options. Persistent connections are disabled as Database::getInstance handles reconnections.
                $options = [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_PERSISTENT => false,
                ];

                // Conditionally add SSL options if enabled in config.php
                if (!empty($dbConfig['ssl']) && $dbConfig['ssl'] === true) {
                    $options[\PDO::MYSQL_ATTR_SSL_CA] = $dbConfig['ssl_ca'] ?? '';
                    $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $dbConfig['ssl_verify'] ?? false;
                }

                return [
                    'dsn' => $dsn,
                    'user' => (string)$dbConfig['user'],
                    'password' => (string)($dbConfig['password'] ?? ''),
                    'options' => $options,
                ];
            })(),
            'mail' => $loadedConfig['mail'] ?? [], // Pass the whole mail config
            'security' => $loadedConfig['security'] ?? [ // Keep defaults
                'session_lifetime' => 7200,
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