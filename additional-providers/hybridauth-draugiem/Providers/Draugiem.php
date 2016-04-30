<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/** 
 * Draugiem Passport
 * 
 * @package             HybridAuth additional providers package 
 * @author              Janis Bebritis / Wunderkraut Latvia <janis.bebritis@wunderkraut.com>
 * @version             0.1
 * @license             BSD License
 */ 
 
/*
  Draugiem.lv is the most popular social network in Latvia with 1200 000+ registered users and 650 000 unique hits per month.

  Draugiem.lv Passport documentation at:
  http://www.frype.com/applications/dev/docs/passport_en/
*/
class Hybrid_Providers_Draugiem extends Hybrid_Provider_Model
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
		
		// include supplied api wrapper
		require_once Hybrid_Auth::$config['path_libraries'] . 'Draugiem/DraugiemApi.php'; 

		//Create Draugiem.lv API object
		$this->api = new DraugiemApi($this->config['keys']['key'], $this->config['keys']['secret']);
		
		//Try to authenticate user
		$session = 	$this->api->getSession(); 

		//Authentication successful
		if($session){
			//Get user info
			$user = $this->api->getUserData();

			$this->user_id = $user['uid'];
		}

	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{

		Hybrid_Auth::redirect($this->api->getLoginUrl($this->endpoint));

	}
 
   /**
	* finish login step 
	*/
	function loginFinish()
	{ 

		if ( ! $_REQUEST['dr_auth_code'] )
		{
			throw new Exception( 'Authentication failed! ' . $this->providerId . ' returned an invalid Token and Verifier.', 5 );
		}
		
		$this->token( 'access_token',	$_REQUEST['dr_auth_code']); 
		
		// set user as logged in
		$this->setUserConnected();
		
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );

	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{

		//Get user info
		$response = $this->api->getUserData();

		if ( ! $response )
		{
			throw new Exception( 'User profile request failed! ' . $this->providerId . ' api returned an invalid response.', 6 );
		}

		$year = $month = $day = null;

		if (isset($response['birthday'])) {
			$birthday = explode("-", $response['birthday']);
			if (count($birthday) === 3) {
				list($year, $month, $day) = $birthday;
			}
		}

		$this->user->profile->identifier    = @ $response['uid'];
		$this->user->profile->displayName  	= $response['name']. ' ' .$response['surname'];
		$this->user->profile->firstName  		= @ $response['name'];
		$this->user->profile->lastName  		= @ $response['surname'];
		$this->user->profile->age  					= @ $response['age'];
		$this->user->profile->birthDay  		= @ $day;
		$this->user->profile->birthMonth  	= @ $month;
		$this->user->profile->birthYear  		= @ $year;
		$this->user->profile->address 			= @ $response['place'];
		$this->user->profile->profileURL 		= @ 'http://www.draugiem.lv/user/' . $response['uid'];
		$this->user->profile->photoURL 			= @ $response['img'];
		$this->user->profile->webSiteURL 		= @ '';
		switch ( $response['sex'] ) {
			case 'M': $this->user->profile->gender = 'male'; break;
			case 'F': $this->user->profile->gender = 'female'; break;
		}
		return $this->user->profile;
	}

}
