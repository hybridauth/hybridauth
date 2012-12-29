<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/** 
 * Murmur OAuth Class
 * 
 * @package             HybridAuth additional providers package 
 * @author              RB Lin <xtheme@gmail.com>
 * @version             1.2
 * @license             BSD License
 */ 

/**
 * Murmur provider adapter based on OAuth1 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Murmur.html
 */
class Hybrid_Providers_Murmur extends Hybrid_Provider_Model_OAuth1
{
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url      = 'http://api.murmur.tw/1/';
		$this->api->authorize_url     = 'http://api.murmur.tw/oauth/authorize';
		$this->api->request_token_url = 'http://api.murmur.tw/oauth/request_token';
		$this->api->access_token_url  = 'http://api.murmur.tw/oauth/access_token';
	}

	/**
	* for Murmur we need to override loginBegin() as the auth url is: $tokens['xoauth_request_auth_url']
	*/
	/*function loginBegin()
	{
		// Get a new request token
		$tokens = $this->api->requestToken( $this->endpoint ); 

		if ( ! isset( $tokens ) ){
			throw new Exception( 'Authentication failed! '.$this->providerId.' returned an invalid Request Token.', 5 );
		}

		$this->token( 'request_token'       ,  $tokens['oauth_token'] ); 
		$this->token( 'request_token_secret',  $tokens['oauth_token_secret'] ); 

		# Build authorize link & redirect user to provider authorisation web page
		Hybrid_Auth::redirect( $tokens['xoauth_request_auth_url'] );
	}*/

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'account/verify_credentials.json' );
		
		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' returned an error. ' . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->id ) ){
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' api returned an invalid response.', 6 );
		}

		$this->user->profile->identifier  = @ $response->id;
		$this->user->profile->displayName = @ $response->name;
		$this->user->profile->description = @ $response->description;
		$this->user->profile->firstName   = @ $response->name; 
		$this->user->profile->photoURL    = @ $response->profile_image_url;
		$this->user->profile->profileURL  = 'http://murmur.tw/' . $response->screen_name;
		$this->user->profile->webSiteURL  = @ $response->url; 
		$this->user->profile->region      = @ $response->location;
		$this->user->profile->city        = @ $response->location;
		$this->user->profile->age         = @ $response->age;
		$this->user->profile->language    = @ $response->lang;

		return $this->user->profile;
	}
	
	/**
	 * load the user contacts
	 */
	function getUserContacts()
	{
		$response = $this->api->get('statuses/friends.json');
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User contacts request failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if ( !$response ) {
			return array();
		}
		
		$contacts = array();
		
		foreach( $response as $item ) {
			$uc = new Hybrid_User_Contact();

			$uc->identifier   = @ $item->id;
			$uc->displayName  = @ $item->name;
			$uc->profileURL   = 'http://murmur.tw/' . $response->screen_name;
			$uc->photoURL     = @ $item->profile_image_url;

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
		$parameters['status'] = $status;

		$response = $this->api->post('statuses/update.json', $parameters); 
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'Update user status failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus( $this->api->http_code ) );
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
		if ( $stream == 'me' ) {
			$url = 'statuses/home_timeline.json';
		} else {
			$url = 'statuses/friends.json';
		}
		
		$response = $this->api->get($url); 
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User activity stream request failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus( $this->api->http_code ) );
		}
		
		if ( ! $response ) {
			return array();
		}
			
		$activities = array();
		
		if ( $stream == 'me' ) {
			foreach ( $response as $item ) {
				$ua = new Hybrid_User_Activity();
				$ua->id                 = @ $item->id;
				$ua->date               = @ $item->timestamp;
				$ua->text               = @ $item->text;
				$ua->user->identifier   = @ $item->user->id;
				$ua->user->displayName  = @ $item->user->name;
				$ua->user->profileURL   = 'http://murmur.tw/' . $item->user->screen_name;
				$ua->user->photoURL     = @ $item->user->profile_image_url;
				
				$activities[] = $ua;
			}
		} else {
			foreach ( $response->news as $item ) {
				if ($item->content_type == 'blog') {
					$ua = new Hybrid_User_Activity();
					$ua->id                 = @ $item->status->id;
					$ua->date               = @ $item->status->public_at;
					$ua->text               = @ $item->status->text;
					$ua->user->identifier   = @ $item->id;
					$ua->user->displayName  = @ $item->name;
					$ua->user->profileURL   = 'http://murmur.tw/' . $item->screen_name;
					$ua->user->photoURL     = @ $item->profile_image_url;
					
					$activities[] = $ua;
				}
			}
		}
		
		return $activities;
	}
}
