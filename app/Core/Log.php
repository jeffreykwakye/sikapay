<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;

class Log
{
    private static ?Logger $logger = null;
    
    // Set up the logger instance once
    private static function initialize(): void
    {
        if (self::$logger === null) {
            $log = new Logger('SikaPay');
            
            // --- Configuration for Rotation and Expiration ---
            $logDirectory = __DIR__ . '/../../logs/'; 
            $maxFiles = 7; 
            $level = Level::Debug; 

            $log->pushHandler(new RotatingFileHandler(
                $logDirectory . 'app.log', 
                $maxFiles,
                $level
            ));
            
            self::$logger = $log;
        }
    }

    // Proxy the Monolog methods
    public static function debug(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->error($message, $context);
    }
    
    public static function critical(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->critical($message, $context);
    }
}