<?php

/**
 * Hybrid_Providers_Strava
 */
class Hybrid_Providers_Strava extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions
	public $scope = "public";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();
		
		// Provider api end-points
		$this->api->api_base_url  = "https://www.strava.com/api/v3/";
		$this->api->authorize_url = "https://www.strava.com/oauth/authorize";	   
		$this->api->token_url     = "https://www.strava.com/oauth/token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile(){
		$data = $this->api->get("athlete"); 
		
		if ( ! isset( $data->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = $data->id; 
		$this->user->profile->username    = $data->username; 
		$this->user->profile->displayName = $data->firstname.' '.$data->lastname;
		$this->user->profile->photoURL    = $data->profile_medium;
		$this->user->profile->profileURL  = 'https://www.strava.com/athletes/'.$data->id; 
		$this->user->profile->email       = $data->email;
		$this->user->profile->emailVerified = $data->email;
		$this->user->profile->gender      = $data->sex;
    $this->user->profile->city        = array_key_exists('city', $data)?$data->city:'';
    $this->user->profile->state       = array_key_exists('state', $data)?$data->state:'';
    $this->user->profile->country     = array_key_exists('country', $data)?$data->country:'';

    return $this->user->profile;
	}
  
}
