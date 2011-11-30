<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Gowalla provider adapter based on OAuth2 protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Gowalla.html
 */
class Hybrid_Providers_Gowalla extends Hybrid_Provider_Model_OAuth2
{ 
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider apis end-points
		$this->api->api_base_url  = "https://api.gowalla.com/";
		$this->api->authorize_url = "https://gowalla.com/api/oauth/new";
		$this->api->token_url     = "https://api.gowalla.com/api/oauth/token"; 

		$this->api->curl_header = array( 'X-Gowalla-API-Key: ' . $this->config["keys"]["id"], 'Accept: application/json' );
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed
		$this->refreshToken();

		$data = $this->api->api( "users/me/" ); 

		if ( ! is_object( $data ) || ! isset( $data->username ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->providerUID         	= @ (string) $data->username; 
		$this->user->profile->firstName  	= @ (string) $data->first_name; 
		$this->user->profile->lastName  	= @ (string) $data->last_name; 
		$this->user->profile->displayName  	= trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );
		$this->user->profile->profileURL  	= @ "http://gowalla.com" . ( (string) $data->url ); 
		$this->user->profile->webSiteURL 	= @ (string) $data->website; 
		$this->user->profile->photoURL   	= @ (string) $data->image_url;

		// make sure to always have a display name
		if( ! $this->user->profile->displayName ){
			$this->user->profile->displayName = @ (string) $data->username; 
		}

		return $this->user->profile;
	}

	function refreshToken()
	{
		// have an access token?
		if( $this->api->access_token ){

			// have to refresh?
			if( $this->api->refresh_token && $this->api->access_token_expires_at ){

				// expired?
				if( $this->api->access_token_expires_at <= time() ){  
					$response = $this->api->refreshToken( array( "refresh_token" => $this->api->refresh_token, "access_token" => $this->api->access_token ) );

					if( ! isset( $response->access_token ) || ! $response->access_token ){
						// set the user as disconnected at this point and throw an exception
						$this->setUserUnconnected();

						throw new Exception( "The Authorization Service has return an invalid response while requesting a new access token. " . (string) $response->error ); 
					}

					// set new access_token
					$this->api->access_token = $response->access_token;

					if( isset( $response->refresh_token ) ) 
					$this->api->refresh_token = $response->refresh_token; 

					if( isset( $response->expires_in ) ){
						$this->api->access_token_expires_in = $response->expires_in;

						// even given by some idp, we should calculate this
						$this->api->access_token_expires_at = time() + $response->expires_in; 
					}
				}
			}

			// re store tokens
			$this->token( "access_token" , $this->api->access_token  );
			$this->token( "refresh_token", $this->api->refresh_token );
			$this->token( "expires_in"   , $this->api->access_token_expires_in );
			$this->token( "expires_at"   , $this->api->access_token_expires_at );
		}
	}
}
