<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2015 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Deprecated;

/**
 * Deprecated methods back yard (kept for backward compatibility sake)
 *
 * These methods are to be removed sooner or later.
 */
trait DeprecatedStorageTrait
{
    /**
    * Get the storage session data into an array
    *
    * @return string|null
    *
    * @deprecated kept for backward compatibility sake
    */
    public function getSessionData()
    {
        if (isset($_SESSION['HA::STORE'])) {
            return serialize($_SESSION['HA::STORE']);
        }
    }

    /**
    * Restore the storage back into session from an array
    *
    * @param string $sessiondata
    *
    * @deprecated kept for backward compatibility sake
    */
    public function restoreSessionData($sessiondata)
    {
        $_SESSION['HA::STORE'] = unserialize($sessiondata);
    }
}
