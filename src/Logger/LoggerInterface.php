<?php
/*!
* HybridAuth
* https://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Logger;

/**
 * Logger interface
 */
interface LoggerInterface
{
    /**
    * Info
    *
    * @param string $message
    *
    * @return boolean
    */
    public function info($message);

    /**
    * Debug
    *
    * @param string $message
    * @param mixed  $object
    *
    * @return boolean
    */
    public function debug($message, $object = null);

    /**
    * Error
    *
    * @param string $message
    * @param mixed  $object
    *
    * @return boolean
    */
    public function error($message, $object = null);
}
