<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Vimeo class, wrapper for Vimeo  
 */
class Hybrid_Providers_Vimeo extends Hybrid_Provider_Model
{ 
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Vimeo/Vimeo.php"; 

		if( $this->token( "access_token" ) && $this->token( "access_token_secret" ) )
		{
			$this->api = new phpVimeo
							( 
								$this->config["keys"]["key"], $this->config["keys"]["secret"],
								$this->token( "access_token" ), $this->token( "access_token_secret" ) 
							);
		}
	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{
		$this->api = new phpVimeo( $this->config["keys"]["key"], $this->config["keys"]["secret"] ); 

 		// Get a new request token
		$tokz = $this->api->getRequestToken( $this->endpoint );

		if ( ! isset( $tokz["oauth_token"] ) )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid Request Token.", 5 );
		}

		$this->token( "request_token"        , $tokz['oauth_token'] ); 
		$this->token( "request_token_secret" , $tokz['oauth_token_secret'] );  

		# Build authorize link & redirect user to vimeo authorisation web page
		Hybrid_Auth::redirect( $this->api->getAuthorizeUrl( $tokz['oauth_token'], 'write' ) ); 
	}
 
   /**
	* finish login step 
	*/
	function loginFinish()
	{ 
		$oauth_token    = @ $_REQUEST['oauth_token']; 
		$oauth_verifier = @ $_REQUEST['oauth_verifier']; 

		if ( ! $oauth_token || ! $oauth_verifier )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid OAuth Token and Verifier.", 5 );
		}

		try{ 
			$this->api = new phpVimeo( 
								$this->config["keys"]["key"], $this->config["keys"]["secret"], 
								$this->token( "request_token" ), $this->token( "request_token_secret" ) 
							);

			$tokz = $this->api->getAccessToken( $oauth_verifier );
		}
		catch( VimeoAPIException $e ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error while requesting a request token. $e.", 5 );
		}

		if ( ! isset( $tokz["oauth_token"] ) )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid Access Token.", 5 );
		}

		// Store tokens 
		$this->token( "access_token"        , $tokz['oauth_token'] ); 
		$this->token( "access_token_secret" , $tokz['oauth_token_secret'] );
 
		// set user as logged in
		$this->setUserConnected();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{ 
			$data = $this->api->call('vimeo.people.getInfo'); 
		}
		catch( VimeoAPIException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile. $e.", 6 );
		} 

		if ( ! is_object( $data ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		} 

		$this->user->profile->identifier    = @ $data->person->id;
		$this->user->profile->displayName  	= @ $data->person->display_name;
		$this->user->profile->address 		= @ $data->person->location;
		$this->user->profile->profileURL 	= @ $data->person->profileurl;
		$this->user->profile->webSiteURL 	= @ $data->person->url[0]; 
		$this->user->profile->description 	= @ $data->person->bio; 
		$this->user->profile->photoURL      = @ $data->person->portraits->portrait[3]->_content; 

		return $this->user->profile;
	}
}
