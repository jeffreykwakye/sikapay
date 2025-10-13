<?php
declare(strict_types=1);
// cli/cli_runner.php

// Ensure this script is run from the command line
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

// Load Composer Autoloader
require __DIR__ . '/../vendor/autoload.php';

use Jeffrey\Sikapay\Core\Database;
use Jeffrey\Sikapay\Config\AppConfig;
use Jeffrey\Sikapay\Commands\MigrationCommand;
use Jeffrey\Sikapay\Commands\SeedCommand;

// 1. Load config file
$config = AppConfig::load();

// 2. Initialize Database connection (needed for all DB commands)
// This calls the static connect() method, which returns the \PDO instance,
// and stores it internally for later retrieval by Database::getInstance().
Database::connect($config['db']); 

// Get the command and arguments from the command line
$args = $argv;
$commandName = $args[1] ?? 'help';
$commandArgs = array_slice($args, 2);

// Simple Command Dispatcher
$commands = [
    'db:migrate'     => MigrationCommand::class,
    'db:seed'        => SeedCommand::class,
    'make:migration' => MigrationCommand::class,
    'help'           => null,
];

if ($commandName === 'help' || !isset($commands[$commandName])) {
    echo "Usage: php cli/cli_runner.php <command> [arguments]\n\n";
    echo "Available Commands:\n";
    foreach (array_keys($commands) as $cmd) {
        if ($cmd !== 'help') echo "  - " . $cmd . "\n";
    }
    echo "  - help\n";
    exit(isset($commands[$commandName]) ? 1 : 0);
}

// Instantiate and run the command
try {
    $commandClass = $commands[$commandName];
    // The command classes will use Database::getInstance() internally
    $command = new $commandClass(); 
    $command->execute($commandName, $commandArgs);
} catch (\Exception $e) {
    echo "\n-------------------------------------\n";
    echo "Command Error: " . $e->getMessage() . "\n";
    echo "-------------------------------------\n";
    exit(1);
}