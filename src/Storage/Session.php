<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Storage;

use Hybridauth\Exception\RuntimeException;

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
    protected $storeNamespace;

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix;

    /**
    * Initiate a new session
    *
    * @param string $storeNamespace
    * @param string $keyPrefix
    *
    * @throws RuntimeException
    */
    public function __construct($storeNamespace = 'HYBRIDAUTH::STORAGE', $keyPrefix = '')
    {
        $this->storeNamespace = $storeNamespace;
        $this->keyPrefix = $keyPrefix;

        if (session_id()) {
            return;
        }

        if (headers_sent()) {
            throw new RuntimeException('HTTP headers already sent to browser and Hybridauth won\'t be able to start/resume PHP session. To resolve this, session_start() must be called before outputing any data.');
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

        if (!$this->storeNamespace && isset($_SESSION[$key])) {
            return $_SESSION[$key];

        } else if (isset($_SESSION[$this->storeNamespace], $_SESSION[$this->storeNamespace][$key])) {
            return $_SESSION[$this->storeNamespace][$key];
        }

        return null;
    }

    /**
    * {@inheritdoc}
    */
    public function set($key, $value)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (!$this->storeNamespace) {
            $_SESSION[$key] = $value;

        } else {
            $_SESSION[$this->storeNamespace][$key] = $value;
        }
    }

    /**
    * {@inheritdoc}
    */
    public function clear()
    {
        if (!$this->storeNamespace) {
            $_SESSION = [];

        } else {
            $_SESSION[$this->storeNamespace] = [];
        }
    }

    /**
    * {@inheritdoc}
    */
    public function delete($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (!$this->storeNamespace && isset($_SESSION[$key])) {
            unset($_SESSION[$key]);

        } else if (isset($_SESSION[$this->storeNamespace], $_SESSION[$this->storeNamespace][$key])) {
            unset($_SESSION[$this->storeNamespace][$key]);
        }
    }

    /**
    * {@inheritdoc}
    */
    public function deleteMatch($key)
    {
        $key = $this->keyPrefix . strtolower($key);

        if (!$this->storeNamespace && count($_SESSION)) {
            foreach ($_SESSION as $k => $v) {
                if (strstr($k, $key)) {
                    unset($_SESSION[$k]);
                }
            }

        } else if (isset($_SESSION[$this->storeNamespace]) && count($_SESSION[$this->storeNamespace])) {
            foreach ($_SESSION[$this->storeNamespace] as $k => $v) {
                if (strstr($k, $key)) {
                    unset($_SESSION[$this->storeNamespace][$k]);
                }
            }
        }
    }
}
