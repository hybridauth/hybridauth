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
	function logout()
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

	/**
	* Make an API call. kept for backward compatibility.
	*
	* @deprecated 
	*/ 
	function api( /* polymorphic */ )
	{
		if( func_num_args() )
		{
			return call_user_func_array( array( $this, 'apiRequest' ), func_get_args() );
		}

		return $this;
	}
}
