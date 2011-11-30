<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/** 
 * Pixnet OAuth Class
 * 
 * @package             HybridAuth additional providers package 
 * @author              RB Lin <xtheme@gmail.com>
 * @version             1.2
 * @license             BSD License
 */ 

/**
 * Pixnet provider adapter based on OAuth1 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Pixnet.html
 */
class Hybrid_Providers_Pixnet extends Hybrid_Provider_Model_OAuth1
{
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url      = 'http://emma.pixnet.cc/';
		$this->api->authorize_url     = 'http://emma.pixnet.cc/oauth/authorize';
		$this->api->request_token_url = 'http://emma.pixnet.cc/oauth/request_token';
		$this->api->access_token_url  = 'http://emma.pixnet.cc/oauth/access_token';
		
		// for access_token need to POST data instead of using GET
		$this->api->access_token_method  = 'POST';
	}

	/**
	 * load the user profile from the IDp api client
	 */
	function getUserProfile()
	{
		$response = $this->api->get('account'); 
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' returned an error. ' . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) )
		{
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' api returned an invalid response.', 6 );
		} 

		$this->user->profile->identifier    = @ $response->account->identity;
		$this->user->profile->displayName  	= @ $response->account->name;
		$this->user->profile->profileURL 	= @ $response->account->link;
		$this->user->profile->photoURL 		= @ $response->account->cavatar;

		switch ( $response->account->gender ) {
			case 'M': $this->user->profile->gender = 'male'; break;
			case 'F': $this->user->profile->gender = 'female'; break;
		}
		
		if ( isset( $response->account->birth ) ) {
			$this->user->profile->birthDay		= substr($response->account->birth, 6);
			$this->user->profile->birthMonth	= substr($response->account->birth, 4, 2);
			$this->user->profile->birthYear		= substr($response->account->birth, 0, 4);
		}
		
		return $this->user->profile;
	}
	
	/**
	 * load the user contacts
	 */
	function getUserContacts()
	{
		$response = $this->api->get('friendships');
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User contacts request failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if ( empty( $response->friend_pairs ) || ( $response->error != 0 ) )
		{
			return array();
		}
		
		$contacts = array();
		
		foreach( $response->friend_pairs as $item ) {
			$uc = new Hybrid_User_Contact();

			$uc->identifier   = @ $item->id;
			$uc->displayName  = @ $item->display_name;
			$uc->profileURL   = 'http://' . strtolower( $item->user_name ) . '.pixnet.net/blog';

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
			if ( isset( $status[0] ) && ! empty( $status[0] ) ) $parameters['title']	 = $status[0];
			if ( isset( $status[1] ) && ! empty( $status[1] ) ) $parameters['body']	 = $status[1];
			if ( isset( $status[2] ) && ! empty( $status[2] ) ) $parameters['status'] = $status[2];
		} else {
			$parameters['title']  = 'Title';
			$parameters['body']	  = $status;
			$parameters['status'] = '1'; // 文章狀態, 0: 刪除, 1: 草稿, 2: 公開, 3: 密碼, 4: 隱藏, 5: 好友, 7: 共同作者
		}
		
		$response = $this->api->post('blog/articles', $parameters); 
		
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
		$parameters = array();
		
		if ( $stream == 'me' ) {
			$url = 'blog/articles';
		} else {
			$parameters['group_type'] = 'friend';
			$url = 'friend/news';
		}
		
		$response = $this->api->get($url, $parameters); 
		
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( 'User activity stream request failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus( $this->api->http_code ) );
		}
		
		$activities = array();
		
		if ( $stream == 'me' ) {
			if ( ! $response || ! count(  $response->articles ) ) {
				return array();
			}
			
			foreach ( $response->articles as $item ) {
				$ua = new Hybrid_User_Activity();
				$ua->id                 = @ $item->id;
				$ua->date               = @ $item->public_at;
				$ua->text               = @ $item->title;
				$ua->user->identifier   = @ $item->user->name;
				$ua->user->displayName  = @ $item->user->display_name;
				$ua->user->profileURL   = @ $item->user->link;
				$ua->user->photoURL     = @ $item->user->cavatar;
				
				$activities[] = $ua;
			}
		} else {
			if ( ! $response || ! count(  $response->news ) ) {
				return array();
			}
			
			foreach ( $response->news as $item ) {
				if ($item->content_type == 'blog') {
					$ua = new Hybrid_User_Activity();
					$ua->id                 = @ $item->blog_article->id;
					$ua->date               = @ $item->blog_article->public_at;
					$ua->text               = @ $item->blog_article->title;
					$ua->user->identifier   = @ $item->user->name;
					$ua->user->displayName  = @ $item->user->display_name;
					$ua->user->profileURL   = @ $item->user->link;
					$ua->user->photoURL     = @ $item->user->cavatar;
					
					$activities[] = $ua;
				}
			}
		}
		
		return $activities;
	}
}
