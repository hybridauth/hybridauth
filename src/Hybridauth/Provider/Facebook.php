<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception;
use Hybridauth\Http\Request;
use Hybridauth\Adapter\Template\OAuth2\OAuth2Template;
use Hybridauth\Entity\Facebook\Profile;
use Hybridauth\Entity\Facebook\Page;
use Hybridauth\Entity\Facebook\Event;

/**
* Facebook adapter extending OAuth2 Template
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html
*/
class Facebook extends OAuth2Template
{
	//Using this so we can access things like pages. Not in love with it.
	private $use_access_token = null;
	function setFacebookAccessToken($use_access_token) {
		$this->use_access_token = $use_access_token;
	}

	function getFacebookAccessToken() {
		return $this->use_access_token;
	}

	function signedRequest( $uri, $method = Request::GET, $parameters = array() )
	{
		if(!isset($parameters[ 'access_token' ]) && !empty($this->use_access_token))
		{
			$parameters[ 'access_token' ] = $this->use_access_token;
		}
		return parent::signedRequest($uri,$method,$parameters);
	}

	/**
	* Internal: Initialize Facebook adapter. This method isn't intended for public consumption.
	*
	* Basically on initializers we feed defaults values to \OAuth2\Template::initialize()
	*
	* let*() methods are similar to set, but 'let' will not overwrite the value if its already set
	*/
	function initialize()
	{
		parent::initialize();

		$this->letApplicationId( $this->getAdapterConfig( 'keys', 'id' ) );
		$this->letApplicationSecret( $this->getAdapterConfig( 'keys', 'secret' ) );

		// @ todo create a way to track scope & request addtl scope as needed
		$scope = $this->getAdapterConfig( 'scope' )
			? $this->getAdapterConfig( 'scope' )
			: 'email,user_about_me,user_birthday,user_hometown,user_website,read_stream,offline_access,publish_stream,read_friendlists';

		$this->letApplicationScope( $scope );

		$this->letEndpointRedirectUri( $this->getHybridauthEndpointUri() );
		$this->letEndpointBaseUri( 'https://graph.facebook.com/' );
		$this->letEndpointAuthorizeUri( 'https://www.facebook.com/dialog/oauth' );
		$this->letEndpointRequestTokenUri( 'https://graph.facebook.com/oauth/access_token' );

		$this->letEndpointAuthorizeUriAdditionalParameters( array( 'display' => 'page' ) );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user profile
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->getUserProfile();
	*/
	function getUserProfile($user = null)
	{
		// request user infos
		$response = $this->signedRequest( isset($user) ? $user->getIdentifier() : 'me' );
		$response = json_decode ( $response );

		if ( ! isset( $response->id ) || isset ( $response->error ) ){
			throw new
				Exception(
					'User profile request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_PROFILE_REQUEST_FAILED,
					$this
				);
		}

		return Profile::generateFromResponse($response,$this);
	}

	// --------------------------------------------------------------------

	/**
	* Returns user contacts list
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->getUserContacts();
	*/
	function getUserContacts()
	{
		$response = $this->signedRequest( 'me/friends' );
		$response = json_decode( $response );

		// Provider Errors shall not pass silently
		if( ! $response || isset( $response->error ) ){
			throw new
				Exception(
					'User contacts request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_CONTACTS_REQUEST_FAILED,
					$this
				);
		}


		$contacts = array();

		if( isset( $response->data ) && is_array( $response->data ) ){
			foreach( $response->data as $item ){
				$contacts[] = Profile::generateFromResponse($item,$this);
			}
		}

		return $contacts;
	}

	// --------------------------------------------------------------------

	/**
	* Returns user profile
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->getUserPages();
	*/
	function getUserPages($user = null)
	{
		// request user infos
		$id = isset($user) ? $user->getIdentifier() : 'me';
		$response = $this->signedRequest( $id . '/accounts' );
		$response = json_decode ( $response );

		if ( ! isset( $response->data ) || isset ( $response->error ) ){
			throw new
				Exception(
					'User page listing request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_PROFILE_REQUEST_FAILED,
					$this
				);
		}


		$pages = array();

		foreach($response->data as $pageData) {
			$pages[] = Page::generateFromResponse($pageData,$this);
		}

		return $pages;
	}

	function getPage($page_id)
	{
		// request user infos
		$response = $this->signedRequest( $page_id);
		$response = json_decode ( $response );

		if ( ! isset( $response->id ) || isset ( $response->error ) ){
			throw new
				Exception(
					'User page listing request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_PROFILE_REQUEST_FAILED,
					$this
				);
		}

		return Page::generateFromResponse($response,$this);
	}

	function getEvent($eventIdentifier)
	{
		$response = $this->signedRequest($eventIdentifier );
		$response = json_decode ( $response );

		if ( ! isset( $response->id ) || isset ( $response->error ) ){
			throw new
				Exception(
					'Event listing request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_PROFILE_REQUEST_FAILED,
					$this
				);
		}
		return Event::generateFromResponse($response);
	}

	// --------------------------------------------------------------------

	/**
	* Updates user status
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->setUserStatus( _STATUS_ );
	*
	*	$data = $hybridauth->authenticate( "Facebook" )->setUserStatus( _PARAMS_ );
	*/
	function setUserStatus( $status )
	{
		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
 	}
}
