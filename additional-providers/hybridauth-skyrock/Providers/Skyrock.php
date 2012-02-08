<?php
/*!
* HybridAuth Skyrock Provider
*
*
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2012 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*
*
*/

/**
* Hybrid_Providers_Skyrock provider adapter based on OAuth1 protocol
*/
class Hybrid_Providers_Skyrock extends Hybrid_Provider_Model_OAuth1
{
	/**
	* IDp wrappers initializer
	*/
	function initialize()
	{
		parent::initialize();

		// provider api end-points
		$this->api->api_base_url      = "https://api.skyrock.com/v2/";
		$this->api->authorize_url     = "https://api.skyrock.com/v2/oauth/authenticate";
		$this->api->request_token_url = "https://api.skyrock.com/v2/oauth/initiate";
		$this->api->access_token_url  = "https://api.skyrock.com/v2/oauth/token";

		$this->api->curl_auth_header  = false;
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'user/get.json' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->id_user ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile.
		$this->user->profile->identifier    = (property_exists($response,'id_user'))?$response->id_user:"";
		$this->user->profile->displayName   = (property_exists($response,'username'))?$response->username:"";
		$this->user->profile->profileURL    = (property_exists($response,'user_url'))?$response->user_url:"";
		$this->user->profile->photoURL      = (property_exists($response,'avatar_url'))?$response->avatar_url:"";
//unknown		$this->user->profile->description   = (property_exists($response,'description'))?$response->description:"";
		$this->user->profile->firstName     = (property_exists($response,'firstname'))?$response->firstname:"";
		$this->user->profile->lastName      = (property_exists($response,'name'))?$response->name:"";

		if( property_exists($response,'gender') ) {
			if( $response->gender == 1 ){
				$this->user->profile->gender = "male";
			}
			elseif( $response->gender == 2 ){
				$this->user->profile->gender = "female";
			}
			else{
				$this->user->profile->gender = "";
			}
		}

		$this->user->profile->language    = (property_exists($response,'lang'))?$response->lang:"";

		if( property_exists( $response,'birth_date' ) && $response->birth_date ) {
                        $birthday = date_parse($response->birth_date);
			$this->user->profile->birthDay   = $birthday["day"];
			$this->user->profile->birthMonth = $birthday["month"];
			$this->user->profile->birthYear  = $birthday["year"];
		}

		$this->user->profile->email         = (property_exists($response,'email'))?$response->email:"";
		$this->user->profile->emailVerified = (property_exists($response,'email'))?$response->email:"";

//unknown		$this->user->profile->phone      = (property_exists($response,'unknown'))?$response->unknown:"";
		$this->user->profile->address       = (property_exists($response,'address1'))?$response->address1:"";
		$this->user->profile->address      .= (property_exists($response,'address2'))?$response->address2:"";
		$this->user->profile->country       = (property_exists($response,'country'))?$response->country:"";
//unknown		$this->user->profile->region      = (property_exists($response,'unknown'))?$response->unknown:"";
		$this->user->profile->city          = (property_exists($response,'city'))?$response->city:"";
		$this->user->profile->zip           = (property_exists($response,'postalcode'))?$response->postalcode:"";

		return $this->user->profile;
	}

	/**
	* load the current user contacts
	*/
	function getUserContacts()
	{
		$parameters = array( 'page' => 1 );
		$response  = $this->api->get( 'user/list_friends.json', $parameters );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if( ! $response || ! count( $response->friends ) ){
			return ARRAY();
		}

		$max_page = (property_exists($response,'max_page'))?$response->max_page:1;
		for ($i = 0; $i<$max_page; $i++) {
			if( $i > 0 ) {
				$parameters = array( 'page' => $i );
				$response  = $this->api->get( 'user/list_friends.json', $parameters );
				// check the last HTTP status code returned
				if ( $this->api->http_code != 200 ){
					throw new Exception( "User contacts request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
				}
			}

			if( $response && count( $response->friends ) ){
				foreach( $response->friends as $item ){
					$uc = new Hybrid_User_Contact();

					$uc->identifier   = (property_exists($item,'id_user'))?$item->id_user:"";
					$uc->displayName  = (property_exists($item,'username'))?$item->username:"";
					$uc->profileURL   = (property_exists($item,'user_url'))?$item->user_url:"";
					$uc->photoURL     = (property_exists($item,'avatar_url'))?$item->avatar_url:"";
					//$uc->description  = (property_exists($item,'description'))?$item->description:"";

					$contacts[] = $uc;
				}
			}
		}

		return $contacts;
	}

	/**
	* return the user activity stream
	*/
	function getUserActivity( $stream )
	{
		if( $stream == "me" ){
			$response  = $this->api->get( 'newsfeed/list_events.json?events_category=own' );
		}
		else{
			$response  = $this->api->get( 'newsfeed/list_events.json?events_category=friends' );
		}

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if( ! $response ){
			return ARRAY();
		}

		$activities = ARRAY();

		foreach( $response as $item ){
			$ua = new Hybrid_User_Activity();

			$ua->id                 = (property_exists($item,'id_event'))?$item->id_event:"";
			$ua->date               = (property_exists($item,'timestamp'))?$item->timestamp:"";
			$ua->text               = (property_exists($item,'content'))?$item->content:"";
			$ua->text               = ($ua->text)?trim(strip_tags($ua->text)):"";

			$ua->user->identifier   = (property_exists($item->from,'id_user'))?$item->from->id_user:"";
			$ua->user->displayName  = (property_exists($item->from,'username'))?$item->from->username:"";
			$ua->user->profileURL   = (property_exists($item->from,'user_url'))?$item->from->user_url:"";
			$ua->user->photoURL     = (property_exists($item->from,'avatar_url'))?$item->from->avatar_url:"";

			$activities[] = $ua;
		}

		return $activities;
	}


	/**
	* update user status
	*/
	function setUserStatus( $status )
	{
		$parameters = array( 'message' => $status );
		$response  = $this->api->post( 'mood/set_mood.json', $parameters );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Update user status failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}
	}

}

