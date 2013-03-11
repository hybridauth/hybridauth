<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception;
use Hybridauth\Adapter\Template\OpenID\OpenIDTemplate;

/**
* OpenID adapter extending OpenID Template
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_OpenID.html
*/
class OpenID extends OpenIDTemplate
{
	/**
	* Internal: Initialize adapter. This method isn't intended for public consumption.
	*
	* Basically on initializers we feed defaults values to \OAuth2\Template::initialize()
	*
	* let*() methods are similar to set, but 'let' will not overwrite the value if its already set
	*/
	function initialize()
	{
		parent::initialize();

		$identifier = $this->getAdapterConfig( 'openid_identifier' ) ? $this->getAdapterConfig( 'openid_identifier' ) 
			: $this->getAdapterParameters( 'openid_identifier' ) ? $this->getAdapterParameters( 'openid_identifier' ) 
			: null;

		$this->letOpenidIdentifier( $identifier );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user profile
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( 'OpenID', 'https://yahoo.com' )->getUserProfile();
	*/
	function getUserProfile()
	{
		return $this->storage->get( $this->providerId . ".user" );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user contacts list 
	*/
	function getUserContacts()
	{
		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
	}

	// --------------------------------------------------------------------

	/**
	* Updates user status 
	*/
	function setUserStatus( $status )
	{
		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
 	}
}
