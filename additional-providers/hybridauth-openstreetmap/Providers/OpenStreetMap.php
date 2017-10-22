<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2017, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
* Hybrid_Providers_OpenStreetMap (openstreetmap.org)
*/
class Hybrid_Providers_OpenStreetMap extends Hybrid_Provider_Model_OAuth1
{
   	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// provider api end-points
		$this->api->api_base_url      = "https://api.openstreetmap.org/api/0.6/";
		$this->api->authorize_url     = "https://www.openstreetmap.org/oauth/authorize";
		$this->api->request_token_url = "https://www.openstreetmap.org/oauth/request_token";
		$this->api->access_token_url  = "https://www.openstreetmap.org/oauth/access_token";

		// turn off json parsing!
		$this->api->decode_json = false;
	}


   /**
	* load the user profile from the api client
	*/
	function getUserProfile()
	{ 
		try{  
			$apiUrl = 'https://api.openstreetmap.org/api/0.6/user/details';
			$response = $this->api->get( $apiUrl, array(), "text/xml" );
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
		}

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		$response = @ new SimpleXMLElement( $response );

		$this->user->profile->identifier    = (string) $response->user["id"];
		$this->user->profile->displayName   = (string) $response->user["display_name"];
		$this->user->profile->description   = (string) $response->user->description;
		$this->user->profile->photoURL      = (string) $response->user->img["href"];

		return $this->user->profile;
 	}

	function httpRequest( $url )
	{
		$ch = curl_init();

		$curl_options = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_USERAGENT      => "WordPress Social Login (https://wordpress.org/plugins/wordpress-social-login/)",
			CURLOPT_MAXREDIRS      => 3,
			CURLOPT_TIMEOUT        => 30
		);

		curl_setopt_array($ch, $curl_options);

		$data = curl_exec($ch);

		return array(
			'response' => $data,
			'info'     => curl_getinfo($ch),
			'error'    => curl_error($ch),
		);
	}
}
