<?php
//
// +---------------------------------------------------------+
// | Writter.php                                             |
// +---------------------------------------------------------+
// | Put your description here                               |
// +---------------------------------------------------------+
// | Copyright (c) 2021                                      |
// +---------------------------------------------------------
// | Authors: Mehernosh Mohta <emnosh.pro@gmail.com.au>      |
// +---------------------------------------------------------+
//


    namespace EM\Log;

    use \DateTimeZone;
    use \DateTime;
    use \RuntimeException;

    class Writer
    {
        protected $_default_options = [
            'date_format' => 'Y-m-d G:i:s.u',
            'timezone' => 'UTC',
            'buffer_size' => 1000,   // no. of lines to write in one go. Not valid for STDOUT
        ];

        public $options = [];
        protected $_log_directory = null;
        protected $_rows = [];
        protected $_file_name = 'php://stdout';

        public function __construct(array $options = [])
        {
            $this->setOptions($options);
        }

        public function setDirectory(string $directory, int $permission = 0777)
        {
            if (!file_exists($directory)) {
                // Note you probably want to specify the permissions as an octal number,
                // which means it should have a leading zero.
                mkdir($directory, $permission, true);
            }

            $this->_log_directory = $directory;
        }

        public function setOptions(array $options)
        {
            if (strpos($this->_file_name, 'php://') === 0) {
                $this->_default_options['buffer_size'] = 0;
            }

            $options = array_merge($this->_default_options, $options);

            $this->options = $options;
        }

        public function setDateFormat(string $date_format)
        {
            $this->options['date_format'] = $date_format;
        }

        public function setBuffer(int $buffer_size)
        {
            $this->options['buffer_size'] = $buffer_size;
        }

        public function formatMessage($type, $message, $context = [])
        {
            $parts = [
                'date' => $this->getTimeStamp(),
                'timezone' => date_default_timezone_get(),
                'level' => strtoupper($type),
                'ip' => $this->getIP(),
            ];

            $parts['meminfo'] = $this->getMemoryPeakUsage();
            $parts['message'] = $this->interpolate($message, $context);

            $message = '';
            foreach ($parts as $part) {
                $message .= '[' . trim($part) . ']';
            }

            $message .= PHP_EOL;

            return $message;
        }

        function interpolate($message, array $context = array())
        {
            // build a replacement array with braces around the context keys
            $replace = array();
            foreach ($context as $key => $val) {
                // check that the value can be cast to string
                if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                    $replace['{' . $key . '}'] = $val;
                }
            }

            // interpolate replacement values into the message and return
            return strtr($message, $replace);
        }

        private function getTimestamp()
        {
            date_default_timezone_set($this->options['timezone']);
            $start = microtime(true);
            $micro = sprintf("%06d", ($start - floor($start)) * 1000000);

            $timezone = new DateTimeZone($this->options['timezone']);
            $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $start), $timezone);
            $date->setTimezone($timezone);

            return $date->format($this->options['date_format']);
        }

        private function workinggetTimestamp()
        {
            date_default_timezone_set("Australia/Brisbane");
            $start = microtime(true);
            $micro = sprintf("%06d", ($start - floor($start)) * 1000000);

            $timezone = new DateTimeZone($this->options['timezone']);
            $date = new DateTime(date('Y-m-d H:i:s.' . $micro, $start), $timezone);
            $date->setTimezone($timezone);

            return $date->format($this->options['date_format']);
        }

        public function write($type, $message, array $context = [])
        {
            if (is_null($message) || empty(trim($message))) {
                return false;
            }

            // we convert the message by formatting and interpolating the text
            $message = $this->formatMessage($type, $message, $context);

            $this->_rows[] = $message;

            if ($this->save() === false) {
                error_log('Failed to write ' . count($this->_rows));
                return;
            }
        }

        public function getData()
        {
            $data = '';

            if (!empty($this->options['buffer_size'])) {
                // avoid frequent writes to the disk
                if (count($this->_rows)) {
                    $data = array_slice($this->_rows, 0, $this->options['buffer_size']);
                    error_log(print_r($data, true));
                }
            } else {
                $data = $this->_rows;

                // clear the buffers
                $this->_rows = [];
            }

            if (count($data)) {
                $data = implode(PHP_EOL, $data);
            }

            return $data;
        }

        public function setLogFile($log_file)
        {
            if (strpos($log_file, 'php://') !== 0) {
                $log_file = $this->_log_directory . '/' . $log_file;

                if (!file_exists($log_file)) {
                    if (touch($log_file) === false) {
                        error_log(__METHOD__.'@'.__LINE__.'Failed to create log file ' . $log_file . PHP_EOL);
                        return;
                    }
                }
            }

            $this->_file_name = $log_file;
        }

        public function save()
        {
            $written = false;

            $data = $this->getData();
            if (empty($data)) {
                return $written;
            }

            if ($this->_file_name
                && strpos($this->_file_name, 'php://') !== 0
            ) {
                $write_mode = 'a';

                $file_handle = fopen($this->_file_name, $write_mode);
                if (!$file_handle) {
                    return $written;
                }

                if (flock($file_handle, LOCK_EX)) {
                    $written = fwrite($file_handle, $data);

                    if ($written !== true) {
                        fflush($file_handle);
                        flock($file_handle, LOCK_UN);
                    }
                }

                fclose($file_handle);
            } else {
                error_log($data);
                $written = true;
            }

            return $written;
        }

        public function getIP()
        {
            if (isset($_SERVER)) {
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    return $_SERVER['REMOTE_ADDR'];
                }

                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }

                if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                    return $_SERVER['HTTP_CLIENT_IP'];
                }
            }

            $host = gethostname();
            $ip = gethostbyname($host);
            if (preg_match('/^192/', $ip)) {
                //$details = json_decode(file_get_contents("http://ipinfo.io/{$ip}"), true);
                $details = json_decode(@file_get_contents("http://ipinfo.io/"), true);
                if (is_array($details) && !empty($details['ip'])) {
                    $ip = $details['ip'];
                }
            }
            return $ip;
        }

        public function getMemoryPeakUsage(): string
        {
            return sprintf('%0.2f', memory_get_peak_usage(false) / 1024 / 1024) . '/' .
                sprintf('%0.2f', memory_get_peak_usage(true) / 1024 / 1024) . ' MiB';
        }

        public function __destruct()
        {
            // if we still have buffer, means there is something still to be written
            // close the handle
            $this->save();
        }
    }
