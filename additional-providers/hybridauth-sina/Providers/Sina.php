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
 * Sina Weibo OAuth
 * 
 * @ID:					Sina
 * @Protocol:			OAuth
 * @IDp URL:			http://www.weibo.com/
 * @Keys registeration:	http://open.weibo.com/development
 * @Dev documentation:	http://open.weibo.com/wiki/API%E6%96%87%E6%A1%A3_V2
 * @Description:		
 * @Author:				RB Lin <xtheme=at=gmail-dot-com>
 * @Based on:			Project Sirius http://code.google.com/p/sirius/
 * @Version:			1.0
 * @Since:				HybridAuth 2.0.6
 * @Wrapper: 			./Hybrid/Providers/Sina.php
 * @Required Libs: 		./Hybrid/thirdparty/Sina/
 * @URL Start login*:	http://mywebsite.com/path_to_hybridauth/?hauth.start=Sina
 * @URL Login done*:	http://mywebsite.com/path_to_hybridauth/?hauth.done=Sina
 */ 
 
/**
 * Hybrid_Providers_Sina class, wrapper for Sina 
 */
class Hybrid_Providers_Sina extends Hybrid_Provider_Model
{ 
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		//date_default_timezone_set ('Etc/GMT-8');
		
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}
		
		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth.php"; 
		require_once Hybrid_Auth::$config["path_libraries"] . "Sina/Sina.php"; 

		if( $this->token( "access_token" ) && $this->token( "access_token_secret" ) ) {
			$this->api = new WeiboOAuth( 
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
		$this->api = new WeiboOAuth( $this->config["keys"]["key"], $this->config["keys"]["secret"] ); 
		
 		// Get a new request token
		$token = $this->api->getRequestToken( $this->endpoint );
		
		if ( ! isset( $token ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid Request Token.", 5 );
		}

		$this->token( "request_token"        , $token['oauth_token'] ); 
		$this->token( "request_token_secret" , $token['oauth_token_secret'] ); 
		
		# Build authorize link & redirect user to vimeo authorisation web page
		Hybrid_Auth::redirect( $this->api->getAuthorizeUrl( $token ) ); 
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
			
			$this->api = new WeiboOAuth( 
				$this->config["keys"]["key"], 
				$this->config["keys"]["secret"],
				$this->token("request_token"), 
				$this->token("request_token_secret") 
			);
			
			$token = $this->api->getAccessToken( $oauth_verifier );

		}
		catch( SinaAPIException $e ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an error while requesting a request token. $e.", 5 );
		}

		if ( ! isset( $token["oauth_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid Access Token.", 5 );
		}

		// Store tokens 
		$this->token( "access_token"        ,	$token['oauth_token'] ); 
		$this->token( "access_token_secret" ,	$token['oauth_token_secret'] );
		$this->token( "access_user_id" , 		$token['user_id'] );
		
		// set user as logged in
		$this->setUserConnected();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{ 
			$profile = $this->api->request_with_uid( 'http://api.t.sina.com.cn/users/show.json', $this->token('access_user_id')); 
			//$profile = $this->api->request_with_uid( 'https://api.weibo.com/2/account/profile/basic.json', $this->token('access_user_id'));
		}
		catch( SinaAPIException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile. $e.", 6 );
		}
		
		if ( ! isset( $profile['id'] ) )
		{ 
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		$this->user->profile->identifier    = @ $profile['id'];
		$this->user->profile->displayName  	= @ $profile['screen_name'];
		$this->user->profile->address 		= @ $profile['location'];
		$this->user->profile->profileURL 	= @ 'http://www.weibo.com/u/'.$profile['id'];
		$this->user->profile->photoURL 		= @ $profile['profile_image_url'];
		$this->user->profile->webSiteURL 	= @ $profile['url'];
		if( isset( $profile['email'] ) ) {
			$this->user->profile->email 	= @ $profile['email'];
		}
		$this->user->profile->gender 		= @ $profile['gender']; // m Male, f Female

		return $this->user->profile;
	}
}
