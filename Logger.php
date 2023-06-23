<?php
//
// +---------------------------------------------------------+
// | Logger.php                                              |
// +---------------------------------------------------------+
// | Put your description here                               |
// +---------------------------------------------------------+
// | Copyright (©) 2021                                      |
// +---------------------------------------------------------+
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au>      |
// +---------------------------------------------------------+
//

    namespace EM\Log;

    use EM\Log\Level as Log_Level;

    class Logger
    {
        public static function info($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::INFO, $message, $context, $log_errors);
        }

        public static function debug($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::DEBUG, $message, $context, $log_errors);
        }

        public static function warning($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::WARNING, $message, $context, $log_errors);
        }

        public static function critical($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::CRITICAL, $message, $context, $log_errors);
        }

        public static function emergency($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::EMERGENCY, $message, $context, $log_errors);
        }

        public static function error($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::ERROR, $message, $context, $log_errors);
            die();
        }

        public static function notice($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::NOTICE, $message, $context, $log_errors);
        }

        public static function alert($message, $context = [], $log_errors = false)
        {
            self::log(Log_Level::ALERT, $message, $context, $log_errors);
        }

        public static function log($type, $message, $context = [], $log_errors = false)
        {
            $log_file = 'php://stdout'; // default stdout

            if ($log_errors) {
                $log_file = LOG_DIR . '/' . $type . '.log';
            }

            $logger = new \EM\Log\Writer();
            $logger->setLogFile($log_file);
            $logger->write($type, $message, $context);
        }
    }
