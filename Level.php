<?php
//
// +---------------------------------------------------------+
// | Level.php                                               |
// +---------------------------------------------------------+
// | Put your description here                               |
// +---------------------------------------------------------+
// | Copyright (©) 2021                                      |
// +---------------------------------------------------------+
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au>      |
// +---------------------------------------------------------+
//
    namespace EM\Log;

    /**
    * Describes log levels.
    */
    class Level
    {
        const EMERGENCY = 'emergency';
        const ALERT     = 'alert';
        const CRITICAL  = 'critical';
        const ERROR     = 'error';
        const WARNING   = 'warning';
        const NOTICE    = 'notice';
        const INFO      = 'info';
        const DEBUG     = 'debug';

        protected static $log_level_types = [
            self::EMERGENCY => 0,
            self::ALERT     => 1,
            self::CRITICAL  => 2,
            self::ERROR     => 3,
            self::WARNING   => 4,
            self::NOTICE    => 5,
            self::INFO      => 6,
            self::DEBUG     => 7
        ];

        protected static $core_level_map = [
            E_ERROR => 'error',
            E_PARSE => 'error',
            E_CORE_ERROR => 'error',
            E_COMPILE_ERROR => 'error',
            E_RECOVERABLE_ERROR => 'error',
            E_ALL => 'error',
            E_USER_ERROR => 'error',
            E_NOTICE => 'notice',
            E_USER_NOTICE => 'notice',
            E_CORE_WARNING => 'warning',
            E_WARNING => 'warning',
            E_USER_WARNING => 'warning',
            E_STRICT => 'info',
            E_DEPRECATED => 'info',
        ];

        public static function getLogLevel($log_level = self::DEBUG): int
        {
            if (isset(self::$log_level_types[$log_level])) {
                return self::$log_level_types[$log_level];
            }

            return self::$log_level_types[self::DEBUG];
        }

        public static function getCoreLevelMap($error_no = E_WARNING): string
        {
            // PHP Core error constants to be mapped
            // such that they can be passed to relevant
            // logger functions
            if (isset(self::$core_level_map[$error_no])) {
                return self::$core_level_map[$error_no];
            }

            return 'info';
        }
    }
