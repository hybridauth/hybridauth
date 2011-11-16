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
 * QQ Weibo OAuth
 * 
 * @ID:					QQ
 * @Protocol:			OAuth
 * @IDp URL:			http://t.qq.com
 * @Keys registeration:	http://open.t.qq.com/development/
 * @Dev documentation:	http://open.t.qq.com/resource.php?i=1,1
 * @Description:		
 * @Author:				RB Lin <xtheme=at=gmail-dot-com>
 * @Based on:			Project Sirius http://code.google.com/p/sirius/
 * @Version:			1.0
 * @Since:				HybridAuth 2.0.6
 * @Wrapper: 			./Hybrid/Providers/QQ.php
 * @Required Libs: 		./Hybrid/thirdparty/QQ/
 * @URL Start login*:	http://mywebsite.com/path_to_hybridauth/?hauth.start=QQ
 * @URL Login done*:	http://mywebsite.com/path_to_hybridauth/?hauth.done=QQ
 */ 
 
/**
 * Hybrid_Providers_QQ class, wrapper for QQ  
 */
class Hybrid_Providers_QQ extends Hybrid_Provider_Model
{ 
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		date_default_timezone_set ('Etc/GMT-8');
		
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}
		
		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth.php"; 
		require_once Hybrid_Auth::$config["path_libraries"] . "QQ/QQ.php"; 

		if( $this->token( "access_token" ) && $this->token( "access_token_secret" ) ) {
			$this->api = new qqOAuth( 
				$this->config["keys"]["key"], 
				$this->config["keys"]["secret"],
				$this->token("access_token"), 
				$this->token("access_token_secret") 
			);
		}
	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{
		$this->api = new qqOAuth( $this->config["keys"]["key"], $this->config["keys"]["secret"] ); 
		
 		// Get a new request token
		$token = $this->api->getRequestToken( $this->endpoint );
		
		if ( ! isset( $token ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid Request Token.", 5 );
		}

		$this->token( "request_token"        , $token['oauth_token'] ); 
		$this->token( "request_token_secret" , $token['oauth_token_secret'] ); 
		
		# Build authorize link & redirect user to vimeo authorisation web page
		Hybrid_Auth::redirect( $this->api->getAuthorizeUrl( $token, urlencode( $this->endpoint ) ) ); 
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
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid OAuth Token and Verifier.", 5 );
		}

		try { 
			
			$this->api = new qqOAuth( 
				$this->config["keys"]["key"], 
				$this->config["keys"]["secret"],
				$this->token("request_token"), 
				$this->token("request_token_secret") 
			);
			
			$token = $this->api->getAccessToken( $oauth_verifier );

		}
		catch( QQAPIException $e ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an error while requesting a request token. $e.", 5 );
		}

		if ( ! isset( $token["oauth_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid Access Token.", 5 );
		}

		// Store tokens 
		$this->token( "access_token"        ,	$token['oauth_token'] ); 
		$this->token( "access_token_secret" ,	$token['oauth_token_secret'] );
		$this->token( "access_name" , 			$token['name'] );
		
		// set user as logged in
		$this->setUserConnected();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{ 
			$profile = $this->api->get('http://open.t.qq.com/api/user/info'); 
		}
		catch( QQAPIException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile. $e.", 6 );
		}
		
		if ( ! isset( $profile['data']['name'] ) )
		{ 
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		$this->user->profile->identifier    = @ $profile['data']['name'];
		$this->user->profile->displayName  	= @ $profile['data']['nick'];
		$this->user->profile->address 		= @ $profile['data']['location'];
		$this->user->profile->profileURL 	= @ 'http://t.qq.com/'.$profile['data']['name'];
		$this->user->profile->photoURL 		= @ $profile['data']['head'];
		$this->user->profile->email 		= @ $profile['data']['email'];
		$this->user->profile->gender 		= @ $profile['data']['sex']; // 1 Male, 2 Female, 0 Unknow
		$this->user->profile->birthDay      = @ $profile['data']['birth_day'];
		$this->user->profile->birthMonth    = @ $profile['data']['birth_month'];
		$this->user->profile->birthYear     = @ $profile['data']['birth_year'];

		return $this->user->profile;
	}
}
