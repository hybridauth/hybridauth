<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/** 
 * Plurk OAuth Class
 * 
 * @package             HybridAuth additional providers package 
 * @author              RB Lin <xtheme@gmail.com>
 * @version             1.2
 * @license             BSD License
 */ 

/**
 * Plurk provider adapter based on OAuth1 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Plurk.html
 */
class Hybrid_Providers_Plurk extends Hybrid_Provider_Model_OAuth1
{
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url      = 'http://www.plurk.com/APP/';
		$this->api->authorize_url     = 'http://www.plurk.com/OAuth/authorize';
		$this->api->request_token_url = 'http://www.plurk.com/OAuth/request_token';
		$this->api->access_token_url  = 'http://www.plurk.com/OAuth/access_token';

		// for Plurk we need to POST data instead of using GET
		$this->api->request_token_method = 'POST';
		$this->api->access_token_method  = 'POST';
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'Profile/getOwnProfile' );
		
		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' returned an error. ' . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->user_info ) ){ 
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' api returned an invalid response.', 6 );
		}
		
		$profile = $response->user_info;

		$this->user->profile->identifier  = @ $profile->uid;
		$this->user->profile->displayName = @ $profile->display_name;
		$this->user->profile->profileURL  = @ 'http://www.plurk.com/' . $profile->nick_name;
		$this->user->profile->region      = @ $profile->location;
		$this->user->profile->photoURL    = $this->getPhotoURL( $profile->uid, $profile->has_profile_image, $profile->avatar );

		if ( ! $this->user->profile->displayName ) {
			$this->user->profile->displayName = $profile->full_name;
		}

		switch ( $profile->gender ) {
			case '1': $this->user->profile->gender = 'male'; break;
			case '2': $this->user->profile->gender = 'female'; break; 
		}

		if( isset( $profile->date_of_birth ) ) {
			$birthday = $profile->date_of_birth;

			$this->user->profile->birthDay      = date( 'd', strtotime($birthday) );
			$this->user->profile->birthMonth    = date( 'm', strtotime($birthday) );
			$this->user->profile->birthYear     = date( 'Y', strtotime($birthday) );
		}

		return $this->user->profile;
	}
	
	/**
	 * load the user contacts
	 */
	function getUserContacts()
	{
		$parameters = array();
		$parameters['user_id'] = $this->user_id;
		$response = $this->api->get('FriendsFans/getFriendsByOffset', $parameters); 
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User contacts request failed! ' . $this->providerId . ' returned an error: ' . $this->api->lastErrorMessageFromStatus() );
		}
		
		if ( !$response ) {
			return array();
		}
		
		$contacts = array();
		
		foreach( $response as $item ) {
			$uc = new Hybrid_User_Contact();

			$uc->identifier   = @ $item->uid;
			$uc->displayName  = @ $item->display_name;
			
			if ( ! $this->user->profile->displayName ) {
				$uc->displayName = @ $item->full_name;
			}
			
			$uc->profileURL   = 'http://www.plurk.com/' . $item->nick_name;
			$uc->photoURL     = $this->getPhotoURL( $item->uid, $item->has_profile_image, $item->avatar );

			$contacts[] = $uc;
		}
		
		return $contacts;
	}
	
	/**
	 * update user status
	 */ 
	function setUserStatus( $status )
	{
		$parameters = array();

		if ( is_array( $status ) ) {
			if ( isset( $status[0] ) && ! empty( $status[0] ) ) $parameters['content']	 = $status[0];
			if ( isset( $status[1] ) && ! empty( $status[1] ) ) $parameters['qualifier'] = $status[1];
			if ( isset( $status[2] ) && ! empty( $status[2] ) ) $parameters['lang']		 = $status[2];
		} else {
			$parameters['content']		= $status;
			$parameters['qualifier']	= 'says';
			$parameters['lang']			= 'en'; // tr_ch
		}
		
		$response = $this->api->get('Timeline/plurkAdd', $parameters); 
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'Update user status failed! ' . $this->providerId . ' returned an error: ' . $this->api->lastErrorMessageFromStatus() );
		}
		
		return $response;
	}
	
	/**
	 * load the user latest activity  
	 *    - timeline : all the stream
	 *    - me       : the user activity only  
	 */
	function getUserActivity( $stream )
	{
		$parameters = array();
		
		if ( $stream == 'me' ) {
			$parameters['filter'] = 'only_user';
		}
		
		$response = $this->api->get('Timeline/getPlurks', $parameters); 
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User activity stream request failed! ' . $this->providerId . ' returned an error: ' . $this->api->lastErrorMessageFromStatus() );
		}
		
		if ( ! $response || ! count(  $response->plurks ) ) {
			return array();
		}
		/*echo '<pre>';
		print_r($response);
		echo '</pre>';*/

		$activities = array();
		
		$users = $response->plurk_users;
		$plurks = $response->plurks;
		
		foreach ( $plurks as $item ) {
			$ua = new Hybrid_User_Activity();
			$ua->id                 = @ $item->plurk_id;
			$ua->date               = @ strtotime( $item->posted );
			$ua->text               = @ $item->content_raw;
			$ua->user->identifier   = @ $item->owner_id;
			$ua->user->displayName  = @ $users->{$item->owner_id}->display_name;
			if ( ! $ua->user->displayName ) {
				$ua->user->displayName  = @ $users->{$item->owner_id}->full_name;
			}
			$ua->user->profileURL   = @ 'http://www.plurk.com/' . $users->{$item->owner_id}->nick_name;
			$ua->user->photoURL = $this->getPhotoURL( $item->owner_id, $users->{$item->owner_id}->has_profile_image, $users->{$item->owner_id}->avatar );
			$activities[] = $ua;
		}
		
		return $activities;
	}
	
	function getPhotoURL( $user_id, $has_profile_image, $avatar )
	{
		$photoURL = 'http://www.plurk.com/static/default_medium.gif';
		
		if ( $has_profile_image == 1 ) {
			if ( $avatar == null ) {
				$photoURL = 'http://avatars.plurk.com/'.$user_id.'-medium.gif';
			} else {
				$photoURL = 'http://avatars.plurk.com/'.$user_id.'-medium'.$avatar.'.gif';
			}
		}
		
		return $photoURL;
	}
}
