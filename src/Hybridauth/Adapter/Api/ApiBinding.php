<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Api;

use Hybridauth\Exception;
use Hybridauth\Adapter\Api\ApiBindingInterface;

class ApiBinding implements ApiBindingInterface
{
	private $_methods     = array ();
	private $_authService = null;

	// --------------------------------------------------------------------

	function bindMethod( $action, $class )
	{
		$this->_methods [$action] = $class;
	}

	// --------------------------------------------------------------------

	function setAuthService( $service )
	{
		$this->_authService = $service;
	}

	// --------------------------------------------------------------------

	function getAuthService()
	{
		return $this->_authService;
	}

	// --------------------------------------------------------------------

	function __call( $action, $arguments )
	{
		if( isset( $this->_methods[$action] ) ){
			$tmp = new $this->_methods[$action]();

			$tmp->setAuthService( $this->getAuthService() );

			return call_user_func_array( array( $tmp, $action ), $arguments );
		}

		throw new Exception( "Provider does not support this feature", Exception::UNSUPPORTED_FEATURE, null, $this );
	}
}
