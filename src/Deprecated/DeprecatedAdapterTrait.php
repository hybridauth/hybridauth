<?php
/*!
* HybridAuth
* https://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Deprecated;

/**
 * Deprecated methods back yard (kept for backward compatibility sake)
 *
 * These methods are to be removed sooner or later.
 */
trait DeprecatedAdapterTrait
{
    /**
    * Alias for disconnect(). kept for backward compatibility.
    *
    * @deprecated
    */
    public function logout()
    {
        $this->disconnect();
    }

    /**
    * Alias for isAuthorized(). kept for backward compatibility.
    *
    * @deprecated
    */
    public function isUserConnected()
    {
        return $this->isAuthorized();
    }
}
