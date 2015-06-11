<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2015 Hybridauth authors | https://hybridauth.github.io/license.html
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
    protected $mode = false;

    /**
    * Path to file writeable by the web server. Required if 'debug_mode' is not false.
    *
    * @var string
    */
    protected $file = '';

    /**
    * @param $mode
    * @param $file
    */
    public function __construct($mode, $file)
    {
        if ($mode) {
            $this->initialize($file);

            $this->mode = $mode;
            $this->file = $file;
        }
    }

    /**
    * @param string $debug_file
    *
    * @throws InvalidArgumentException
    * @throws RuntimeException
    */
    protected function initialize($file)
    {
        if (! $file) {
            throw new InvalidArgumentException("'debug_mode' is set to 'true' but the log file path 'debug_file' is not given.");
        }

        if (! file_exists($file) && ! touch($file)) {
            throw new RuntimeException("'debug_mode' is set to 'true', but the file 'debug_file' in 'debug_file' can not be created.");
        }

        if (! is_writable($file)) {
            throw new RuntimeException("'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writeable.");
        }
    }

    /**
    * {@inheritdoc}
    */
    public function info($message)
    {
        if ('error' === $this->mode) {
            return false;
        }

        $this->_write('INFO', $message);
    }

    /**
    * {@inheritdoc}
    */
    public function debug($message, $object = null)
    {
        if (true !== $this->mode) {
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
        if (! $this->mode) {
            return false;
        }

        $datetime = new \DateTime();
        $datetime =  $datetime->format(DATE_ATOM);

        $content  = $level . ' -- ' . $_SERVER['REMOTE_ADDR'] . ' -- ' . $datetime . ' -- ' . $message . ' -- ';
        $content .= ($object ? print_r($object, true) : '');
        $content .= "\n";

        file_put_contents($this->file, $content, FILE_APPEND);
    }
}
