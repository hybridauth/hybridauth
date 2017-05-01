<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2012 HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
* Hybrid_Providers_Instagram (By Sebastian Lasse - https://github.com/sebilasse)
*/
class Hybrid_Providers_Instagram extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions   
	public $scope = "basic"; 

	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.instagram.com/v1/";
		$this->api->authorize_url = "https://api.instagram.com/oauth/authorize/";
		$this->api->token_url     = "https://api.instagram.com/oauth/access_token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile(){ 
		$data = $this->api->api("users/self/" ); 

		if ( $data->meta->code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = $data->data->id; 
		$this->user->profile->displayName = $data->data->full_name ? $data->data->full_name : $data->data->username; 
		$this->user->profile->description = $data->data->bio;
		$this->user->profile->photoURL    = $data->data->profile_picture;

		$this->user->profile->webSiteURL  = $data->data->website; 
		
		$this->user->profile->username    = $data->data->username;	

		return $this->user->profile;
	}
	/**
	*
	*/
	function getUserContacts() {
		// refresh tokens if needed
		$this->refreshToken();

		//
		$response = array();
		$contacts = array();
        $profile = ( ( isset( $this->user->profile->identifier ) )?( $this->user->profile ):( $this->getUserProfile() ) );
		try {
            $response = $this->api->api( "users/{$this->user->profile->identifier}/follows" );
        } catch (Exception $e) {
            throw new Exception("User contacts request failed! {$this->providerId} returned an error: $e");
        }
        //

		if ( isset( $response ) && $response->meta->code == 200 ) {
			foreach ($response->data as $contact) {
                try {
                    $contactInfo = $this->api->api( "users/".$contact->id );
                } catch (Exception $e) {
                    throw new Exception("Contact info request failed for user {$contact->username}! {$this->providerId} returned an error: $e");
                }
                //
				$uc = new Hybrid_User_Contact();
				//
				$uc->identifier     = $contact->id;
				$uc->profileURL     = "https://instagram.com/{$contact->username}";
				$uc->webSiteURL     = @$contactInfo->data->website;
				$uc->photoURL       = @$contact->profile_picture;
				$uc->displayName    = @$contact->full_name;
				$uc->description	= @$contactInfo->data->bio;
				//$uc->email          = ;
				//
				$contacts[] = $uc;
			}
		}
		return $contacts;
	}
}
