<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like Facebook,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/

/**
 * Hybrid_Providers_Gowalla class, wrapper for Gowalla  
 */
class Hybrid_Providers_Gowalla extends Hybrid_Provider_Model
{ 
	var $redirect_uri = NULL;  
	
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Gowalla/Gowalla.php";

		$this->redirect_uri = $this->endpoint . "&";

		// If we have an access token, we try to init the gowalla api with it
		if ( $this->token( "access_token" ) ){
            // inti gowalla api with the old access_token
            $this->api = new Gowalla( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri, $this->token( "access_token" ) );

            // check if the token has expired,
            if( strtotime( $this->token( "expires_in" ) ) <= strtotime("now") ){
                // call Gowalla::refreshToken() to get new tokens
                $response = $this->api->refreshToken( $this->token( "access_token" ), $this->token( "refresh_token" ) )

                // check if gowalla response is valid 
                if ( ! isset( $response["access_token"] ) ){
                    // set the user as disconnected at this point and throw an exception
                    $this->setUserUnconnected();

                    throw new Exception( "Authentification failed! {$this->providerId} access token has expired and returned an invalid refresh token.", 5 );
                }

                // store the new access token, refresh token and the access token expire time
                $this->token( "access_token"  , $response['access_token']  );
                $this->token( "refresh_token" , $response['refresh_token'] );
                $this->token( "expires_in"    , $response['expires_in']    );

                // inti gowalla api with the new access_token
                $this->api = new Gowalla( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri, $this->token( "access_token" ) );
            }
		}

        // else we dont have an access token stored
        else{
            $this->api = new Gowalla( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri );
        }
	}

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		// authenticate app
		$this->api->authenticate();
	}

	/**
	* finish login step 
	*/ 
	function loginFinish()
	{ 
		$code  = @$_REQUEST['code'];

		$response = $this->api->requestToken( $code );

		if ( ! $response || ! isset( $response["access_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid access token.", 5 );
		}

        // set access token, refresh token and access token expire time
        $this->token( "scope"         , $response['scope']         );
        $this->token( "access_token"  , $response['access_token']  );
        $this->token( "refresh_token" , $response['refresh_token'] );
        $this->token( "expires_in"    , $response['expires_in']    );

		// set user as logged in
		$this->setUserConnected();
 	}
	
   /**
	* load the user profile from the IDp api client 
	*/
	function getUserProfile()
	{
		$response = $this->api->getMe();

		if ( ! $response || ! isset( $response["username"] ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->providerUID         	= @ (string) $response["username"]; 
		$this->user->profile->firstName  	= @ (string) $response["first_name"]; 
		$this->user->profile->lastName  	= @ (string) $response["last_name"]; 
		$this->user->profile->displayName  	= trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );

		if( isset( $response["url"] ) ){
			$this->user->profile->profileURL = @ "http://gowalla.com" . ( (string) $response["url"] ); 
		}

		$this->user->profile->webSiteURL 	= @ (string) $response["website"]; 
		$this->user->profile->photoURL   	= @ (string) $response["image_url"]; 

		return $this->user->profile;
	}
}
