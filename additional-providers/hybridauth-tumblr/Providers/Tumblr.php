<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
* Hybrid_Providers_Tumblr 
*/
class Hybrid_Providers_Tumblr extends Hybrid_Provider_Model_OAuth1
{
   	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}
		// provider api end-points
		$this->api->api_base_url      = "http://www.tumblr.com/";
		$this->api->authorize_url     = "http://www.tumblr.com/oauth/authorize";
		$this->api->request_token_url = "http://www.tumblr.com/oauth/request_token";
		$this->api->access_token_url  = "http://www.tumblr.com/oauth/access_token";
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$this->api->decode_json=false;
		$response = $this->api->get( 'http://www.tumblr.com/api/authenticate' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		try{ 
			$profile = $this->api->get( 'http://api.tumblr.com/v2/user/info' );
			foreach ($profile->response->user->blogs as &$blog) {
				if($blog->primary>0){
					$url = $blog->url;
					$p = explode('://', $url);
					$base_host = $p[1];
				}
			}
			$avatar = $this->api->get( 'http://api.tumblr.com/v2/blog/'.$base_host.'avatar' );
			
			$this->user->profile->identifier 	= $url;
			$this->user->profile->displayName	= $profile->response->user->name;
			$this->user->profile->profileURL	= $url;
			$this->user->profile->webSiteURL	= $url;
			$this->user->profile->photoURL 		= $avatar->response->avatar_url;
			
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
		}	

		return $this->user->profile;
 	}

   	/**
	* load the current logged in user contacts list from the IDp api client  
	*/
	function getUserContacts() 
	{
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

   	/**
	* return the user activity stream  
	*/
	function getUserActivity( $stream ) 
	{
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

   	/**
	* return the user activity stream  
	*/ 
	function setUserStatus( $status )
	{
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}
}
