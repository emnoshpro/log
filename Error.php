<?php
//
// +----------------------------------------------------+
// | Error.php                                          |
// +----------------------------------------------------+
// | Put your description here                          |
// +----------------------------------------------------+
// | Copyright (©) 2021                                 |
// +----------------------------------------------------+
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au> |
// +----------------------------------------------------+
//

    namespace EM\Log;

    class Error
    {
        protected $_error_reporting = 0;
        protected $_display_errors = 0;
        protected $_log_errors = false;

        public function __construct(
            bool $debug = false,
            $error_reporting = -1,
            $log_errors = false
        )
        {
            if ($debug === true) {
                $this->_display_errors = 1;
                ini_set('report_memleaks', 1);
            }

            $this->_log_errors = $log_errors;

            $this->_error_reporting = $error_reporting;
            error_reporting($this->_error_reporting);

            ini_set('display_errors', $this->_display_errors);

            $this->registerHandlers();
        }

        public function registerHandlers()
        {
            register_shutdown_function([$this, 'shutDown']);
            set_error_handler([$this, 'handler'], $this->_error_reporting);
            set_exception_handler([$this, 'handleException']);
        }

        public function handleException($exception)
        {
            $error_type = $exception->getCode() ? $exception->getCode() : E_ERROR;
            $this->handler($error_type,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTrace()
            );
        }

        public function shutDown()
        {
            // NOTE: this gets called regardless
            $error = error_get_last();
            // certain times, warnings are not caught in the error handler
            if ($error) {
                // file_get_contents() fetching from a url wil fail when the service is down
                // this throws a E_WARNING which is not caught in the error handler
                $this->handler($error['type'], $error['message'], $error['file'], $error['line'], []);
            }
        }

        public function handler($error_no, $error_string = null, $error_file = null, $error_line = null, $context = [])
        {
            // NOTE: if error has been surpressed with an @
            if (!(error_reporting() & $error_no)) {
                return;
            }

            if (func_num_args() === 5) {
                // trigger_error
                [$error_no, $error_string, $error_file, $error_line, $context] = func_get_args();

                $stack = array_reverse(debug_backtrace());
                array_shift($stack); // remove {main}
                array_pop($stack); // remove call to this method
            } else {
                // exceptions
                $exception = func_get_arg(0);

                $error_no = $exception->getCode();
                $error_string = $exception->getMessage();
                $error_file = $exception->getFile();
                $error_line = $exception->getLine();

                $stack = $exception->getTrace();
            }

            $trace = null;
            if (count($stack)) {
                $counter = 0;
                $trace = 'trace' . PHP_EOL;
                foreach ($stack as $value) {
                    $trace .= '#' . $counter++ . ' ';
                    if (isset($value['class'])) {
                        $trace .= $value['class'];
                    }

                    if (isset($value['function'])) {
                        if (!empty($trace)) {
                            $trace .= ':' . $value['function'];
                        } else {
                            $trace .= $value['function'];
                        }
                    }

                    if (isset($value['args'])) {
                        $args = '';
                        foreach ($value['args'] as $arg) {
                            if (!empty($args)) {
                                $args .= ',';
                            }
                            $args .= Debug::getArgument($arg);
                        }
                    }

                    $trace .= '(';
                    if (!empty($args)) {
                        $trace .= $args;
                    }
                    $trace .= ')' . PHP_EOL;
                }
            }

            $message = $error_string . ' in ' . $error_file . ' on line ' .  $error_line . ' {bt}';

            $type = Level::getCoreLevelMap($error_no);
            Logger::$type($message, [ 'bt' => $trace ], $this->_log_errors);
        }
    }
