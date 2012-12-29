<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
* 
* Provider writed by xbreaker | https://github.com/xbreaker/hybridauth
*/

/**
 * Hybrid_Providers_Mailru provider adapter based on OAuth2 protocol
 * 
 */
class Hybrid_Providers_Mailru extends Hybrid_Provider_Model_OAuth2
{ 
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider apis end-points
		$this->api->api_base_url  = "http://www.appsmail.ru/platform/api";
		$this->api->authorize_url = "https://connect.mail.ru/oauth/authorize";
		$this->api->token_url     = "https://connect.mail.ru/oauth/token"; 
		$this->api->sign_token_name = "session_key";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
    $sig = md5( "client_id=" . $this->api->client_id . "format=jsonmethod=users.getInfosecure=1session_key=". $this->api->access_token . $this->api->client_secret );
		$response = $this->api->api( "?format=json&client_id=" . $this->api->client_id . "&method=users.getInfo&secure=1&sig=" .$sig); 
    if ( ! isset( $response[0]->uid ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}
    
    $response = $response[0];
    
    $this->user->profile->identifier    = (property_exists($response,'uid'))?$response->uid:"";
		$this->user->profile->firstName     = (property_exists($response,'first_name'))?$response->first_name:"";
		$this->user->profile->lastName      = (property_exists($response,'last_name'))?$response->last_name:"";
		$this->user->profile->displayName   = (property_exists($response,'nick'))?$response->nick:"";
		$this->user->profile->photoURL      = (property_exists($response,'pic'))?$response->pic:"";
		$this->user->profile->profileURL    = (property_exists($response,'link'))?$response->link:"";
		$this->user->profile->gender        = (property_exists($response,'sex'))?$response->sex:""; 
		$this->user->profile->email         = (property_exists($response,'email'))?$response->email:"";
		$this->user->profile->emailVerified = (property_exists($response,'email'))?$response->email:"";

		if( property_exists($response,'birthday') ){ 
			list($birthday_day, $birthday_month, $birthday_year) = explode( '.', $response->birthday );

			$this->user->profile->birthDay   = (int) $birthday_day;
			$this->user->profile->birthMonth = (int) $birthday_month;
			$this->user->profile->birthYear  = (int) $birthday_year;
		}

		return $this->user->profile;
	}
}
