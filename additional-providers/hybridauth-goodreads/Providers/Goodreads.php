<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
* Hybrid_Providers_Goodreads 
*/
class Hybrid_Providers_Goodreads extends Hybrid_Provider_Model_OAuth1
{
   	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// provider api end-points
		$this->api->api_base_url      = "http://www.goodreads.com/";
		$this->api->authorize_url     = "http://www.goodreads.com/oauth/authorize";
		$this->api->request_token_url = "http://www.goodreads.com/oauth/request_token";
		$this->api->access_token_url  = "http://www.goodreads.com/oauth/access_token";

		// turn off json parsing!
		$this->api->decode_json = false;
	}

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		// in case we get authorize=0
		if ( ! isset($_REQUEST['oauth_token']) || ( isset( $_REQUEST['authorize'] ) && $_REQUEST['authorize'] == "0" ) ){ 
			throw new Exception( "Authentication failed! The user denied your request.", 5 );
		}

		$oauth_verifier = @ $_REQUEST['oauth_token'];

		if ( !$oauth_verifier ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth verifier.", 5 );
		}

		// request an access token
		$tokens = $this->api->accessToken( $oauth_verifier );

		// access tokens as received from provider
		$this->access_tokens_raw = $tokens;

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		// we should have an access_token, or else, something has gone wrong
		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		// we no more need to store request tokens
		$this->deleteToken( "request_token"        );
		$this->deleteToken( "request_token_secret" );

		// store access_token for later user
		$this->token( "access_token"        , $tokens['oauth_token'] );
		$this->token( "access_token_secret" , $tokens['oauth_token_secret'] ); 

		// set user as logged in to the current provider
		$this->setUserConnected(); 
	}
	
	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'http://www.goodreads.com/api/auth_user' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		// parse the response 
		$response = @ new SimpleXMLElement( $response );

		$this->user->profile->identifier  = (string) $response->user['id'];
		$this->user->profile->displayName = (string) $response->user->name;
		$this->user->profile->profileURL  = (string) $response->user->link; 

		// try to grab more information about the user if possible
		$response = $this->api->get( 'http://www.goodreads.com/user/show/' . $this->user->profile->identifier . '.xml' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			return $this->user->profile;
		}

		// parse the response 
		$response = @ new SimpleXMLElement( $response );

		$this->user->profile->photoURL    = (string) $response->user->image_url; 
		$this->user->profile->webSiteURL  = (string) $response->user->website; 
		$this->user->profile->description = (string) $response->user->about; 
		$this->user->profile->country     = (string) $response->user->location; 
		$this->user->profile->gender      = (string) $response->user->gender; 
		$this->user->profile->age         = (string) $response->user->age; 
		
		return $this->user->profile;
 	}
}
