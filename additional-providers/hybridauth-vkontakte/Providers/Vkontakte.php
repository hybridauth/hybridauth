<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Vkontakte provider adapter based on OAuth2 protocol
 *
 * added by guiltar | https://github.com/guiltar
 */

class Hybrid_Providers_Vkontakte extends Hybrid_Provider_Model_OAuth2
{
	// default permissions 
	public $scope = "";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->authorize_url  = "http://api.vk.com/oauth/authorize";
		$this->api->token_url      = "https://api.vk.com/oauth/token";
		//$this->api->token_info_url
	}

	function loginFinish()
	{
		$error = (array_key_exists('error',$_REQUEST))?$_REQUEST['error']:"";

		// check for errors
		if ( $error ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error: $error", 5 );
		}

		// try to authenticate user
		$code = (array_key_exists('code',$_REQUEST))?$_REQUEST['code']:"";

		try{
			$response = $this->api->authenticate( $code );
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		}

		// check if authenticated
		if ( !property_exists($response,'user_id') || ! $this->api->access_token ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		// store tokens
		$this->token( "access_token" , $this->api->access_token  );
		$this->token( "refresh_token", $this->api->refresh_token );
		$this->token( "expires_in"   , $this->api->access_token_expires_in );
		$this->token( "expires_at"   , $this->api->access_token_expires_at );

		// store user id. it is required for api access to Vkontakte
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user_id", $response->user_id );

		// set user connected locally
		$this->setUserConnected();
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed 
		$this->refreshToken();

		// Vkontakte requires user id, not just token for api access
		$params['uid'] = Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.user_id" );
		$params['fields'] = 'first_name,last_name,nickname,screen_name,sex,bdate,timezone,photo_rec,photo_big';
		// ask vkontakte api for user infos
		$response = $this->api->api( "https://api.vk.com/method/getProfiles" , 'GET', $params);


		if (!isset( $response->response[0] ) || !isset( $response->response[0]->uid ) || isset( $response->error ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$response = $response->response[0];
		$this->user->profile->identifier    = (property_exists($response,'uid'))?$response->uid:"";
		$this->user->profile->firstName     = (property_exists($response,'first_name'))?$response->first_name:"";
		$this->user->profile->lastName      = (property_exists($response,'last_name'))?$response->last_name:"";
		$this->user->profile->displayName   = (property_exists($response,'screen_name'))?$response->screen_name:"";
		$this->user->profile->photoURL      = (property_exists($response,'photo_big'))?$response->photo_big:"";
		$this->user->profile->profileURL    = (property_exists($response,'screen_name'))?"http://vk.com/" . $response->screen_name:"";

		if(property_exists($response,'sex')){
			switch ($response->sex)
			{
				case 1: $this->user->profile->gender = 'female'; break;
				case 2: $this->user->profile->gender = 'male'; break;
				default: $this->user->profile->gender = ''; break;
			}
		}

		if( property_exists($response,'bdate') ){
			
			$birthday = explode('.', $response->bdate);
			
			if (count($birthday) === 3) {
				list($birthday_year, $birthday_month, $birthday_day) = $birthday;
			} else {
				$birthday_year = date('Y');
				list($birthday_month, $birthday_day) = $birthday;
			}

			$this->user->profile->birthDay   = (int) $birthday_day;
			$this->user->profile->birthMonth = (int) $birthday_month;
			$this->user->profile->birthYear  = (int) $birthday_year;
		}

		return $this->user->profile;
	}
		
	/**
	* load the user contacts
	*/
	function getUserContacts() 
	{
		$params=array(
			'fields' => 'nickname, domain, sex, bdate, city, country, timezone, photo_200_orig'
		);
		
		$response = $this->api->api('https://api.vk.com/method/friends.get','GET',$params);
		
		if(!$response || !count($response->response)){
			return array();
		}
		
		$contacts = array();
		foreach( $response->response as $item ){
			$uc = new Hybrid_User_Contact();
			$uc->identifier  = $item->uid;
			$uc->displayName = $item->first_name.' '.$item->last_name;
			$uc->profileURL  = 'http://vk.com/'.$item->domain;
			$uc->photoURL    = $item->photo_200_orig;
			$contacts[] = $uc;
		}
		
		return $contacts;
	}
}
