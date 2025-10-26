<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Core;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;

class Log
{
    private static ?Logger $logger = null;
    
    /**
     * Set up the logger instance once (Singleton).
     */
    private static function initialize(): void
    {
        if (self::$logger === null) {
            $log = new Logger('SikaPay');
            
            // --- Configuration for Rotation and Expiration ---
            $logDirectory = __DIR__ . '/../../logs/'; 
            $maxFiles = 7; 
            $level = Level::Debug; // Log everything from Debug up

            $log->pushHandler(new RotatingFileHandler(
                $logDirectory . 'app.log', 
                $maxFiles,
                $level
            ));
            
            self::$logger = $log;
        }
    }

    /**
     * Detailed debug information.
     */
    public static function debug(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->debug($message, $context);
    }

    /**
     * Interesting events.
     */
    public static function info(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->info($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     */
    public static function warning(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->warning($message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     */
    public static function error(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->error($message, $context);
    }


    /**
     * Action must be taken immediately. Alerts are typically for critical conditions
     * that require immediate attention (e.g., security violation like failed CSRF).
     */
    public static function alert(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->alert($message, $context);
    }

    
    /**
     * Critical conditions.
     */
    public static function critical(string $message, array $context = []): void
    {
        self::initialize();
        self::$logger->critical($message, $context);
    }
}