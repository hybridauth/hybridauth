<?php
//!! planned to replace Y! openid adapter on 2.0.10, under..

/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
* Hybrid_Providers_Yahoo provider adapter based on OAuth1 protocol
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Yahoo.html
*/
class Hybrid_Providers_Yahoo extends Hybrid_Provider_Model_OAuth1
{
	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url      = "http://social.yahooapis.com/v1/";
		$this->api->authorize_url     = "https://api.login.yahoo.com/oauth/v2/request_auth";
		$this->api->request_token_url = "https://api.login.yahoo.com/oauth/v2/get_request_token";
		$this->api->access_token_url  = "https://api.login.yahoo.com/oauth/v2/get_token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( '.' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}
 
	// tODo

		return $this->user->profile;
 	}
}
