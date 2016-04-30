<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/** 
 * Sina OAuth Class
 * 
 * @package             HybridAuth additional providers package 
 * @author              RB Lin <xtheme@gmail.com>
 * @version             1.2
 * @license             BSD License
 */ 
 
/**
 * Sina provider adapter based on OAuth1 protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Sina.html
 */
class Hybrid_Providers_Sina extends Hybrid_Provider_Model
{ 
	public $user_id;
	
	/**
	 * IDp wrappers initializer 
	 */
	function initialize() 
	{
		if ( ! $this->config['keys']['key'] || ! $this->config['keys']['secret'] )
		{
			throw new Exception( 'Your application key and secret are required in order to connect to ' . $this->providerId . '.', 4 );
		}
		
		require_once Hybrid_Auth::$config['path_libraries'] . 'Sina/saetv2.ex.class.php'; 

		if ( $this->token( 'access_token' ) ) {
			$this->api = new SaeTClientV2 ( 
				$this->config['keys']['key'], $this->config['keys']['secret'], $this->token('access_token')
			);
			
			$user = $this->api->get_uid();
			$this->user_id = $user['uid'];
		} else {
			$this->api = new SaeTOAuthV2 ( 
				$this->config['keys']['key'], $this->config['keys']['secret'] 
			);
		}
	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{
		Hybrid_Auth::redirect( $this->api->getAuthorizeURL( $this->endpoint ) ); 
	}
 
   /**
	* finish login step 
	*/
	function loginFinish()
	{ 
		if ( ! $_REQUEST['code'] )
		{
			throw new Exception( 'Authentication failed! ' . $this->providerId . ' returned an invalid OAuth Token and Verifier.', 5 );
		}
		

		$params = array();
		$params['code'] = $_REQUEST['code'];
		$params['redirect_uri'] = $this->endpoint;
		
		try {
			$tokz = $this->api->getAccessToken( 'code', $params ) ;
		} catch (OAuthException $e) {
			throw new Exception( 'Authentication failed! ' . $this->providerId . ' returned an invalid Access Token.', 5 );
		}

		// Store tokens 
		$this->token( 'access_token',	$tokz['access_token'] ); 
		
		// set user as logged in
		$this->setUserConnected();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->show_user_by_id($this->user_id); 

		if ( $this->api->oauth->http_code != 200 )
		{
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' returned an error: ' . $response['error'], 6 );
		}

		if ( ! $response )
		{
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' api returned an invalid response.', 6 );
		}

		$this->user->profile->identifier    = @ $response['id'];
		$this->user->profile->displayName  	= @ $response['screen_name'];
		$this->user->profile->address 		= @ $response['location'];
		$this->user->profile->profileURL 	= @ 'http://www.weibo.com/u/' . $response['id'];
		$this->user->profile->photoURL 		= @ $response['profile_image_url'];
		$this->user->profile->webSiteURL 	= @ $response['url'];
		switch ( $response['gender'] ) {
			case 'm': $this->user->profile->gender = 'male'; break;
			case 'f': $this->user->profile->gender = 'female'; break;
		}
		
		return $this->user->profile;
	}
	
	/**
	 * load the user contacts
	 */
	function getUserContacts()
	{
		$params = array();
		$params['uid'] = $this->user_id;
		$params['cursor'] = 0;
		$params['count'] = 10;
		
		$response = $this->api->oauth->get( 'friendships/friends', $params );

		if ( $this->api->oauth->http_code != 200 )
		{
			throw new Exception( 'User contacts request failed! ' . $this->providerId . ' returned an error: ' . $response['error'] );
		}

		if ( !$response )
		{
			return array();
		}
		
		$contacts = array();
		
		foreach( $response['users'] as $item ) {
			$uc = new Hybrid_User_Contact();

			$uc->identifier   = @ $item['id'];
			$uc->displayName  = @ $item['name'];
			$uc->profileURL   = @ $item['url'];
			$uc->photoURL     = @ $item['profile_image_url'];
			$uc->description  = @ $item['description'];

			$contacts[] = $uc;
		}
		
		return $contacts;
	}
	
	/**
	 * update user status
	 */ 
	function setUserStatus( $status )
	{
		$response = $this->api->update($status); 
		
		if ( $this->api->oauth->http_code != 200 )
		{
			throw new Exception( 'Update user status failed! ' . $this->providerId . ' returned an error: ' . $response['error'] );
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
		$page = 1;
		$count = 10;
		
		if ( $stream == 'me' )
		{
			$response = $this->api->user_timeline_by_id($this->user_id, $page, $count); 
		} else {
			$response = $this->api->home_timeline($page, $count); 
		}
		
		if ( $this->api->oauth->http_code != 200 )
		{
			throw new Exception( 'User activity stream request failed! ' . $this->providerId . ' returned an error: ' . $response['error'] );
		}
		
		$activities = array();
		
		if ( $response['total_number'] == 0 ) 
		{
			return $activities;
		}
		
		foreach ( $response['statuses']  as $item ) 
		{
			$ua = new Hybrid_User_Activity();
			$ua->id                 = @ $item['id'];
			$ua->date               = @ strtotime($item['created_at']);
			$ua->text               = @ $item['text'];
			$ua->user->identifier   = @ $item['user']['id'];
			$ua->user->displayName  = @ $item['user']['screen_name'];
			$ua->user->profileURL   = 'http://www.weibo.com/u/' . $item['user']['id'];
			$ua->user->photoURL     = $item['user']['profile_image_url'];
			
			$activities[] = $ua;
		}
		
		return $activities;
	}
}
