<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Logger;

use Hybridauth\Exception\RuntimeException;
use Hybridauth\Exception\InvalidArgumentException;

/**
 * Debugging and Logging utility.
 */
class Logger implements LoggerInterface
{
    const NONE  = 'none';
    const ERROR = 'error';
    const INFO  = 'info';
    const DEBUG = 'debug';

    /**
     * Debug level.
     *
     * One of Logger::NONE, Logger::ERROR, Logger::INFO, Logger::DEBUG
     *
     * @var string
     */
    protected $level;

    /**
     * Path to file writeable by the web server. Required if $this->level !== Logger::NONE.
     *
     * @var string
     */
    protected $file;

    /**
     * @param bool|string $level One of Logger::NONE, Logger::ERROR, Logger::INFO, Logger::DEBUG
     * @param string      $file  File where t write messages
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct($level, $file)
    {
        $this->level = self::NONE;

        if ($level && $level !== self::NONE) {
            $this->initialize($file);

            $this->level = $level;
            $this->file = $file;
        }
    }

    /**
     * @param string $file
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function initialize($file)
    {
        if (!$file) {
            throw new InvalidArgumentException('Log file is not specified.');
        }

        if (!file_exists($file) && !touch($file)) {
            throw new RuntimeException(sprintf('Log file %s can not be created.', $file));
        }

        if (!is_writable($file)) {
            throw new RuntimeException(sprintf('Log file %s is not writeable.', $file));
        }
    }

    /**
     * @inheritdoc
     */
    public function info($message, array $context = [])
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function debug($message, array $context = [])
    {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $context = [])
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = [])
    {
        // We do not log anything if:
        // 1) Logging disabled
        // 2) Configured logging level is less detailed than $level
        //
        // Logging levels priority from less to more detailed, next level implies all previous
        // * ERROR
        // * INFO
        // * DEBUG
        if (
            $this->level === self::NONE ||
            (
                $level === self::DEBUG &&
                $this->level !== self::DEBUG
            ) ||
            (
                $level === self::INFO &&
                !in_array($this->level, [self::DEBUG, self::INFO], true)
            )
        ) {
            return;
        }

        $datetime = new \DateTime();
        $datetime = $datetime->format(DATE_ATOM);

        $content = sprintf('%s -- %s -- %s -- %s', $level, $_SERVER['REMOTE_ADDR'], $datetime, $message);
        $content .= ($context ? "\n".print_r($context, true) : '');
        $content .= "\n";

        file_put_contents($this->file, $content, FILE_APPEND);
    }
}
