<?php

namespace Imee\Comp\Nocode\Apijson\Utils;

class Logger
{
    private static $logFile = null;
    
    public static function init()
    {
        if (self::$logFile === null) {
            // 优先读取全局定义的 CACHE_DIR
            if (defined('CACHE_DIR') && CACHE_DIR) {
                $baseDir = rtrim(CACHE_DIR, DS);
            } else {
                $baseDir = ROOT . DS . 'cache';
            }
            $logDir = $baseDir . DS . 'log';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . DS . 'apijson_debug.log';
            
            // 设置error_log路径
            ini_set('log_errors', 1);
            ini_set('error_log', self::$logFile);
        }
    }
    
    public static function log($message, $level = 'INFO')
    {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        // 写入到专用日志文件
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // 同时使用error_log输出到标准日志
        error_log($message);
    }
    
    public static function debug($message)
    {
        self::log($message, 'DEBUG');
    }
    
    public static function info($message)
    {
        self::log($message, 'INFO');
    }
    
    public static function warning($message)
    {
        self::log($message, 'WARNING');
    }
    
    public static function error($message)
    {
        self::log($message, 'ERROR');
    }
} 