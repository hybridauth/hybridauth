<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Api;

class AbstractApiOperations
{
	private $_authService = null;

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

	function get( $uri, $parameters = array() )
	{
		return $this->getAuthService()->signedRequest ( $uri, 'GET', $parameters );
	}

	// --------------------------------------------------------------------

	function post( $uri, $parameters = array() )
	{
		return $this->getAuthService()->signedRequest ( $uri, 'POST', $parameters );
	}
}
