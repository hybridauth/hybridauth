<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception;
use Hybridauth\Adapter\Template\OAuth1\OAuth1Template;
use Hybridauth\Entity\Profile;

/**
* Yahoo adapter extending OAuth1 Template
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Yahoo.html
*/
class Yahoo extends OAuth1Template
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

		$this->letApplicationKey( $this->getAdapterConfig( 'keys', 'key' ) );
		$this->letApplicationSecret( $this->getAdapterConfig( 'keys', 'secret' ) );

		$scope = $this->getAdapterConfig( 'scope' )
			? $this->getAdapterConfig( 'scope' )
			: 'r_basicprofile+r_emailaddress+rw_nus';

		$this->letEndpointRedirectUri( $this->getHybridauthEndpointUri() );
		$this->letEndpointBaseUri( 'http://social.yahooapis.com/v1/' );
		$this->letEndpointAuthorizeUri( 'https://api.login.yahoo.com/oauth/v2/request_auth' );
		$this->letEndpointRequestTokenUri( 'https://api.login.yahoo.com/oauth/v2/get_request_token' );
		$this->letEndpointAccessTokenUri( 'https://api.login.yahoo.com/oauth/v2/get_token' );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user profile
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Yahoo" )->getUserProfile();
	*/
	function getUserProfile()
	{
		/// ToDo

		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user contacts list
	*/
	function getUserContacts()
	{
		/// ToDo

		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
	}

	// --------------------------------------------------------------------

	/**
	* Updates user status
	*/
	function setUserStatus( $status )
	{
		/// ToDo

		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
 	}
}
