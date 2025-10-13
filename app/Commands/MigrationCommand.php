<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Commands;

use Jeffrey\Sikapay\Core\Database;

class MigrationCommand
{
    private \PDO $db;
    // Path to the migration files
    private string $migrationsPath = __DIR__ . '/../../database/migrations/';

    public function __construct()
    {
        // Get the established PDO instance
        $this->db = Database::getInstance() ?? throw new \Exception("Database connection is required for migration commands.");
    }

    public function execute(string $command, array $args): void
    {
        if ($command === 'db:migrate') {
            $this->runMigrations();
        } elseif ($command === 'make:migration') {
            $this->makeMigration($args[0] ?? '');
        } else {
            echo "Unknown migration command.\n";
        }
    }

    private function runMigrations(): void
    {
        echo "Starting migrations...\n";
        $this->ensureMigrationsTableExists();
        
        $appliedMigrations = $this->getAppliedMigrations();
        $files = scandir($this->migrationsPath);
        // Filter out non-SQL files and already applied migrations
        $pendingMigrations = array_filter(
            array_diff($files, ['.', '..']),
            function($file) use ($appliedMigrations) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'sql' && !in_array($file, $appliedMigrations);
            }
        );
        // Sort migrations to run in correct timestamp order
        sort($pendingMigrations);

        if (empty($pendingMigrations)) {
            echo "No pending migrations found.\n";
            return;
        }

        foreach ($pendingMigrations as $file) {
            echo "Applying: {$file}...\n";
            $sql = file_get_contents($this->migrationsPath . $file);
            
            try {
                // Execute SQL script
                $this->db->exec($sql);
                $this->saveMigration($file);
                echo " [SUCCESS]\n";
            } catch (\PDOException $e) {
                echo " [ERROR]: " . $e->getMessage() . "\n";
                // Crucial: Stop on first failure
                exit(1);
            }
        }
        echo "Migrations finished.\n";
    }

    private function makeMigration(string $name): void
    {
        if (empty($name)) {
            echo "Error: Migration name required.\n";
            return;
        }
        
        $name = strtolower(str_replace('-', '_', $name));
        $timestamp = date('Ymd_His');
        $filename = "{$timestamp}_{$name}.sql";
        $filepath = $this->migrationsPath . $filename;
        
        // Ensure the directory exists
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0777, true);
        }

        // Create the file with a template
        $template = "-- Migration: {$name}\n\n";
        file_put_contents($filepath, $template);

        echo "Created migration: {$filepath}\n";
    }

    private function ensureMigrationsTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=INNODB;";
        $this->db->exec($sql);
    }

    private function getAppliedMigrations(): array
    {
        // Check if the table exists before querying it
        try {
            $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id");
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            // Table doesn't exist yet, return empty array
            return [];
        }
    }

    private function saveMigration(string $file): void
    {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $stmt->execute([':migration' => $file]);
    }
}