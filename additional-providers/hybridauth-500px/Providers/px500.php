<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
* Hybrid_Providers_px500 (500px.com)
*/
class Hybrid_Providers_px500 extends Hybrid_Provider_Model_OAuth1
{
   	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// provider api end-points
		$this->api->api_base_url      = "https://api.500px.com/v1/";
		$this->api->authorize_url     = "https://api.500px.com/v1/oauth/authorize";
		$this->api->request_token_url = "https://api.500px.com/v1/oauth/request_token";
		$this->api->access_token_url  = "https://api.500px.com/v1/oauth/access_token";

		$this->api->curl_auth_header  = false;
	}


   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{ 
	

		try{  
			$response = $this->api->get( 'users' );

			$this->user->profile->identifier    = (property_exists($response->user,'id'))?$response->user->id:"";
			$this->user->profile->displayName   = (property_exists($response->user,'username'))?$response->user->username:"";
			$this->user->profile->description   = (property_exists($response->user,'about'))?$response->user->about:"";
			$this->user->profile->firstName     = (property_exists($response->user,'firstname'))?$response->user->firstname:"";
			$this->user->profile->lastName      = (property_exists($response->user,'lastname'))?$response->user->lastname:"";  
			$this->user->profile->photoURL      = (property_exists($response->user,'userpic_url'))?$response->user->userpic_url:"";
			$this->user->profile->profileURL    = (property_exists($response->user,'domain'))?("http://".$response->user->domain):"";
			$this->user->profile->webSiteURL    = (property_exists($response->user->contacts,'website'))?$response->user->contacts->website:""; 
			$this->user->profile->city          = (property_exists($response->user,'city'))?$response->user->city:"";
			$this->user->profile->region        = (property_exists($response->user,'state'))?$response->user->state:"";
			$this->user->profile->country       = (property_exists($response->user,'country'))?$response->user->country:"";

			if(property_exists($response->user,'sex')){
				if($response->user->sex>0){
					$this->user->profile->gender   = ($response->user->sex==1)?"male":"female";
				}
			}

			return $this->user->profile; 
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
		}

		return $this->user->profile;
 	}

   	/**
	* post to 500px
	*/ 
	function setUserStatus( $status )
	{
		// README : posting to a 500px.com blog requires the post's TITLE to be set somehow
		// So it is strongly recommended that you submit status as an ARRAY, like :
		
		// setUserStatus( array( 'title'=>'YOUR TITLE HERE', 'body'=>'YOUR MESSAGE HERE' ) )
		
		
		if(is_array($status) && isset($status['title']) && isset($status['body'])){
			$t = $status['title'];
			$b = $status['body'];
		} else {
			$t = '...';
			$b = $status;	
		}
		
		$parameters = array( 'title' => $t, 'body' => $b ); 
		$response  = $this->api->post( 'blogs', $parameters );  

		if ( property_exists($response,'id') ){
			return $response->id;
		} else {
			throw new Exception( "Update user status failed! {$this->providerId} returned an error. "  );	
		}
		
		// this function is for 'plain' blog posting only :
		// we will commit photo upload soon in an extra function, called setUpload -
		// because 500px users can also get an additional Upload Key to upload pictures
		// refer to  http://developers.500px.com/docs/upload-post  for now
                
                return $response;
	}
}
