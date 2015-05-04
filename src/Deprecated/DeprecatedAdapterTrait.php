<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
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
