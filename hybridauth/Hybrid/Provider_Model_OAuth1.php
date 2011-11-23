<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

class Hybrid_Provider_Model_OAuth1 extends Hybrid_Provider_Model
{
	/**
	* try to get the error message from provider api
	*/ 
	function errorMessageByStatus() { 
		$http_status_codes = ARRAY(
			200 => "OK: Success!",
			304 => "Not Modified: There was no new data to return.",
			400 => "Bad Request: The request was invalid.",
			401 => "Unauthorized.",
			403 => "Forbidden: The request is understood, but it has been refused.",
			404 => "Not Found: The URI requested is invalid or the resource requested does not exists.",
			406 => "Not Acceptable.", 
			500 => "Internal Server Error: Something is broken.",
			502 => "Bad Gateway.",
			503 => "Service Unavailable."
		);

		if( $this->api && isset( $http_status_codes[$this->api->http_code] ) ) 
		return  $this->api->http_code . " " .$http_status_codes[ $this->api->http_code ];
	}


	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		// check application credentials
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] ){
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		// include OAuth lib and client
		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth1Client.php";

		// setup access_token if any stored
		if( $this->token( "access_token" ) ){
			$this->api = new OAuth1Client( 
				$this->config["keys"]["key"], $this->config["keys"]["secret"],
				$this->token( "access_token" ), $this->token( "access_token_secret" ) 
			);
		}
		// setup request_token if any stored, in order to exchange with an access token
		elseif( $this->token( "request_token" ) ){
			$this->api = new OAuth1Client( 
				$this->config["keys"]["key"], $this->config["keys"]["secret"], 
				$this->token( "request_token" ), $this->token( "request_token_secret" ) 
			);
		}
		// instanciate OAuth client with client credentials
		else{
			$this->api = new OAuth1Client( $this->config["keys"]["key"], $this->config["keys"]["secret"] );
		}
	}

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$tokens = $this->api->requestToken( $this->endpoint ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid oauth token.", 5 );
		}

		$this->token( "request_token"       , $tokens["oauth_token"] ); 
		$this->token( "request_token_secret", $tokens["oauth_token_secret"] ); 

		# redirect user to twitter 
		Hybrid_Auth::redirect( $this->api->authorizeUrl( $tokens ) );
	}

	/**
	* finish login step 
	*/ 
	function loginFinish()
	{
		$oauth_token    = @ $_REQUEST['oauth_token']; 
		$oauth_verifier = @ $_REQUEST['oauth_verifier']; 

		if ( ! $oauth_token || ! $oauth_verifier ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid oauth verifier.", 5 );
		}

		$tokens = $this->api->accessToken( $oauth_verifier );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		$this->deleteToken( "request_token"        );
		$this->deleteToken( "request_token_secret" );
		$this->token( "access_token"        , $tokens['oauth_token'] );
		$this->token( "access_token_secret" , $tokens['oauth_token_secret'] ); 

		// set user as logged in
		$this->setUserConnected();
	}
}
