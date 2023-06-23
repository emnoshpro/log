<?php
//
// +-----------------------------------------------------------+
// | Debug.php                                                 |
// +-----------------------------------------------------------+
// | Put your description here                                 |
// +-----------------------------------------------------------+
// | Copyright (©) 2022                                        |
// +-----------------------------------------------------------+
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au>        |
// +-----------------------------------------------------------+
//
    namespace EM\Log;

    class Debug
    {
        public static function getArgument($arg)
        {
            switch (strtolower(gettype($arg))) {

                case 'string':
                    return ('"' . str_replace(array("\n"), array(''), $arg) . '"');

                case 'boolean':
                    return (bool)$arg;

                case 'object':
                    return 'Object(' . get_class($arg) . ')';

                case 'array':
                    return 'Array(' . count($arg) . ')';

                case 'resource':
                    return 'Resource('.get_resource_type($arg).')';

                default:
                    return var_export($arg, true);
            }
        }

        public static function log($arg, String $message = null)
        {
            error_log(__METHOD__.'@'.__LINE__);
            ob_start();
            var_dump($arg);
            $contents = ob_get_contents();
            ob_end_clean();

            if (!is_null($message)) {
                error_log($message);
            }
            error_log(str_replace(["\r", "\n"], [''], $contents));
        }
    }
