<?php
/*!
* HybridAuth
* https://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Logger;

use Hybridauth\Exception\RuntimeException;
use Hybridauth\Exception\InvalidArgumentException;

/**
 * Debugging and Logging utility
 */
class Logger implements LoggerInterface
{
    /**
    * Debug level
    *
    * If you want to enable logging, set 'debug_mode' to:
    *
    *     false  Disable logging.
    *      true  Enable logging. When set to TRUE, all logging levels will be saved in log file.
    *   "error"  Only log error messages.
    *    "info"  Log info and error messages (ignore debug messages).
    *
    * @var mixed
    */
    protected $debug_mode = false;

    /**
    * Path to file writeable by the web server. Required if 'debug_mode' is not false.
    *
    * @var string
    */
    protected $debug_file = '';

    /**
    * @param $debug_mode
    * @param $debug_file
    */
    public function __construct($debug_mode, $debug_file)
    {
        if ($debug_mode) {
            $this->initialize($debug_file);

            $this->debug_mode = $debug_mode;
            $this->debug_file = $debug_file;
        }
    }

    /**
    * @param string $debug_file
    *
    * @throws InvalidArgumentException
    * @throws RuntimeException
    */
    protected function initialize($debug_file)
    {
        if (! $debug_file) {
            throw new InvalidArgumentException("'debug_mode' is set to 'true' but the log file path 'debug_file' is not given.");
        }

        if (! file_exists($debug_file) && ! touch($debug_file)) {
            throw new RuntimeException("'debug_mode' is set to 'true', but the file 'debug_file' in 'debug_file' can not be created.");
        }

        if (! is_writable($debug_file)) {
            throw new RuntimeException("'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writeable.");
        }
    }

    /**
    * {@inheritdoc}
    */
    public function info($message)
    {
        if ('error' === $this->debug_mode) {
            return false;
        }

        $this->_write('INFO', $message);
    }

    /**
    * {@inheritdoc}
    */
    public function debug($message, $object = null)
    {
        if (true !== $this->debug_mode) {
            return false;
        }

        $this->_write('DEBUG', $message, $object);
    }

    /**
    * {@inheritdoc}
    */
    public function error($message, $object = null)
    {
        $this->_write('ERROR', $message, $object);
    }

    /**
    * Write a message to log file and return TRUE if entry was written to log.
    *
    * @param string $level Error level
    * @param string $message Error message
    * @param mixed  $object
    *
    * @return boolean
    */
    private function _write($level, $message, $object = null)
    {
        if (! $this->debug_mode) {
            return false;
        }

        $datetime = new \DateTime();
        $datetime =  $datetime->format(DATE_ATOM);

        $content  = $level . ' -- ' . $_SERVER['REMOTE_ADDR'] . ' -- ' . $datetime . ' -- ' . $message . ' -- ';
        $content .= ($object ? print_r($object, true) : '');
        $content .= "\n";

        file_put_contents($this->debug_file, $content, FILE_APPEND);
    }
}
