<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Storage;

use Hybridauth\Exception\RuntimeException;
use Hybridauth\Deprecated\DeprecatedStorageTrait;

/**
 * HybridAuth storage manager
 */
class Session implements StorageInterface
{
    /**
     * Namespace
     *
     * @var string
     */
    protected $namespace = 'HA:STORE';

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix = 'hauth_session.';

    /**
    * Initiate a new session
    *
    * @throws RuntimeException
    */
    public function __construct()
    {
        if (session_id()) {
            return;
        }

        if (headers_sent()) {
            throw new RuntimeException('Hybridauth wasn\'t able to start PHP session. HTTP headers already sent.');
        }

        if (! session_start()) {
            throw new RuntimeException('PHP session failed to start.');
        }
    }

    /**
    * {@inheritdoc}
    */
    public function get($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->namespace], $_SESSION[$this->namespace][$key])) {
            return unserialize($_SESSION[$this->namespace][$key]);
        }

        return null;
    }

    /**
    * {@inheritdoc}
    */
    public function set($key, $value)
    {
        $key = $this->keyPrefix . strtolower($key);

        $_SESSION[$this->namespace][$key] = serialize($value);
    }

    /**
    * {@inheritdoc}
    */
    public function clear()
    {
        $_SESSION[$this->namespace] = [];
    }

    /**
    * {@inheritdoc}
    */
    public function delete($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->namespace], $_SESSION[$this->namespace][$key])) {
            $tmp = $_SESSION[$this->namespace];

            unset($tmp[$key]);

            $_SESSION[$this->namespace] = $tmp;
        }
    }

    /**
    * {@inheritdoc}
    */
    public function deleteMatch($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (isset($_SESSION[$this->namespace]) && count($_SESSION[$this->namespace])) {
            $tmp = $_SESSION[$this->namespace];

            foreach ($tmp as $k => $v) {
                if (strstr($k, $key)) {
                    unset($tmp[ $k ]);
                }
            }

            $_SESSION[$this->namespace] = $tmp;
        }
    }
}
