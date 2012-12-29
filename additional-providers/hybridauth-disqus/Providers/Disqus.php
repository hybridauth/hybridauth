<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Disqus - DO NOT USE, does not work yet
 * says wrong API version, wrote a mail to Disqus support
 */
class Hybrid_Providers_Disqus extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions  
	public $scope = "read,write";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();
		// Provider api end-points
		$this->api->api_base_url  = "https://disqus.com/api/3.0/";
		$this->api->authorize_url = "https://disqus.com/api/3.0/oauth/2.0/authorize";	   
		$this->api->token_url     = "https://disqus.com/api/3.0/oauth/2.0/access_token";
		
		$this->api->curl_header = array( 'client_id: ' . $this->config["keys"]["id"], 'Accept: application/json' );
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$data = $this->api->get( "users/details" ); 
		print_r($data);
		if ( ! isset( $data->code ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		} else if ( $data->code != 0 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned error code".$data->code.".", 6 );
		}

		$this->user->profile->identifier  = @ $data->id; 
		$this->user->profile->displayName = @ $data->name;
		$this->user->profile->description = @ $data->bio;
		$this->user->profile->photoURL    = @ $data->avatar_url;
		$this->user->profile->profileURL  = @ $data->html_url; 
		$this->user->profile->email       = @ $data->email;
		$this->user->profile->webSiteURL  = @ $data->blog;
		$this->user->profile->region      = @ $data->location;

		if( ! $this->user->profile->displayName ){
			$this->user->profile->displayName = @ $data->login;
		}

		return $this->user->profile;
	}
}
