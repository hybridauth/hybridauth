<?php

/**
 * PHP Gowalla
 * A class to communicate with the Gowalla API
 *
 * @author		Lester Lievens <lievens.lester@gmail.com>
 * @version		2.0
 *
 * @link		http://lester.be/projects/php-gowalla
 *
 * @copyright	Copyright (c) 2010, Lester Lievens. All rights reserved.
 * @license		BSD License
 */
class Gowalla
{
	// URLs that we use
	const API_OAUTH_URL = 'https://api.gowalla.com/';
	const OAUTH_URL = 'https://gowalla.com/api/oauth/new?';
	const OAUTH_TOKEN_URL = 'https://api.gowalla.com/api/oauth/token';

	// current version
	const VERSION = '2.0';


	/**
	 * The registered client id of the app
	 *
	 * @var string
	 */
	private $clientId;


	/**
	 * The registered client secret of the app
	 *
	 * @var string
	 */
	private $clientSecret;


	/**
	 * The access token to make calls
	 *
	 * @var string
	 */
	private $accessToken;


	/**
	 * Timeout
	 *
	 * @var int
	 */
	private $timeout = 50;


	/**
	 * The registered redirect uri of the app
	 *
	 * @var string
	 */
	private $redirectURI;


	/**
	 * The user agent
	 *
	 * @var string
	 */
	private $userAgent;


	/**
	 * A cURL instance
	 *
	 * @var	resource
	 */
	private $cURL;


	/**
	 * Default Constructor
	 *
	 * @return	void
	 * @param	string $clientId		The registered client id of the Gowalla app
	 * @param	string $clientSecret	The registered client secret of the Gowalla app
	 * @param	string $redirectURI		The registered redirect uri of the Gowalla app
	 * @param	string $accessToken		The received access token
	 */
	public function __construct($clientId, $clientSecret, $redirectURI, $accessToken = null)
	{
		$this->clientId = (string) $clientId;
		$this->clientSecret = (string) $clientSecret;
		$this->redirectURI = (string) $redirectURI;
		$this->accessToken = (string) $accessToken;
	}


	/**
	 * Default Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		// shutdown connection
		if($this->cURL != null) curl_close($this->cURL);
	}


	/**
	 * Lets the user authenticate an app at the Gowalla website
	 *
	 * @return	void
	 */
	public function authenticate()
	{
		//only redirect if gowalla hasn't given us a code
		if(!isset($_GET['code']))
		{
			//build query string
			$queryString = http_build_query(array('client_id' => $this->clientId, 'scope' => 'read-write', 'grant_type' => 'authorization_code', 'redirect_uri' => $this->redirectURI));

			// redirect to gowalla authentication page
			header('Location: '. self::OAUTH_URL . $queryString);

			// exit
			exit();
		}
	}


	/**
	 * Make a curl api call to Gowalla
	 *
	 * @return	array
	 * @param	string $URL			The url to make the call to
	 * @param	array $parameters	The parameters to send
	 * @param 	string $method		GET or POST call
	 */
	private function doAPICall($URL, array $parameters = null, $method = 'GET')
	{
		// redefine
		$URL = (string) $URL;
		$parameters = (array) $parameters;
		$method = (string) $method;

		// add access token to parameters
		$parameters['oauth_token'] = $this->accessToken;

		// build querystring
		$queryString = http_build_query($parameters);

		// set options
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_TIMEOUT] = $this->getTimeOut();
		$options[CURLOPT_RETURNTRANSFER] = true;
		// $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = true;
		$options[CURLOPT_HTTPHEADER] = array( 'X-Gowalla-API-Key: ' . $this->clientId,
											  'Accept: application/json' );

		// method is post
		if($method == 'POST')
		{
			// set URL
			$options[CURLOPT_URL] = self::API_OAUTH_URL . $URL;

			// set post
			$options[CURLOPT_POST] = true;

			// set parameters
			$options[CURLOPT_POSTFIELDS] = $queryString;
		}

		// method is get -> set URL with parameters
		else $options[CURLOPT_URL] = self::API_OAUTH_URL . $URL . $queryString;

		// init
		if($this->cURL == null) $this->cURL = curl_init();

		// apply options
		curl_setopt_array($this->cURL, $options);

		// fetch data
		$data = curl_exec($this->cURL);

		// fetch errors
		$errorNumber = curl_errno($this->cURL);
		$errorMessage = curl_error($this->cURL);

		// error in call?
		if($errorNumber != '') throw new GowallaException($errorMessage, $errorNumber);

		// get data in assoc array
		$data = json_decode($data, true);

		// data = null?
		if($data === null) throw new GowallaException('Unknown error occured. Gowalla returned null.');

		// error in data?
		if(array_key_exists('error', $data)) throw new GowallaException($data['error']);

		// return data
		return $data;
	}


	/**
	 * Checkin at a particular spot
	 *
	 * @return	array
	 * @param	int $spotId				The id of the spot to checkin
	 * @param	string $comment			The comment of the checkin
	 * @param	float $lat				The latitude of the users current position
	 * @param	float $lng				The longitude of the users current position
	 * @param	bool $postToTwitter		Should we post to twitter?
	 * @param	bool $postToFacebook	Should we post to facebook?
	 */
	public function doCheckin($spotId, $comment, $lat, $lng, $postToTwitter, $postToFacebook)
	{
		// build parameters array
		$parameters = array( 'spot_id' => (int) $spotId,
							 'comment' => (string) $comment,
							 'lat' => (float) $lat,
							 'lng' => (float) $lng,
							 'post_to_twitter' => (bool) $postToTwitter,
							 'post_to_facebook' => (bool) $postToFacebook);

		// return checkin info
		return $this->doAPICall('checkins/?', $parameters, 'POST');
	}


	/**
	 * Make a curl token call
	 *
	 * @return	array
	 * @param	array $parameters		The parameters to send
	 */
	private function doTokenCall(array $parameters)
	{
		// build querystring
		$queryString = http_build_query($parameters);

		// set options
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_TIMEOUT] = $this->getTimeOut();
		$options[CURLOPT_RETURNTRANSFER] = true;
		// $options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_SSL_VERIFYPEER] = false;
		$options[CURLOPT_SSL_VERIFYHOST] = true;
		$options[CURLOPT_HTTPHEADER] = array('Accept: application/json');
		$options[CURLOPT_URL] = self::OAUTH_TOKEN_URL;
		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = $queryString;

		// init
		if($this->cURL == null) $this->cURL = curl_init();

		// apply options
		curl_setopt_array($this->cURL, $options);

		// fetch data
		$data = curl_exec($this->cURL);

		// fetch errors
		$errorNumber = curl_errno($this->cURL);
		$errorMessage = curl_error($this->cURL);

		// error?
		if($errorNumber != '') throw new GowallaException($errorMessage, $errorNumber);

		// return data in assoc array
		return json_decode($data, true);
	}


	/**
	 * Retrieve information about a specific checkin
	 *
	 * @return	array
	 * @param	int $checkinId		The id of the checkin to get the info for
	 */
	public function getCheckin($checkinId)
	{
		// redefine
		$checkinId = (int) $checkinId;

		// return info
		return $this->doAPICall('/checkins/'. $checkinId .'/?');
	}


	/**
	 * Retrieve information about a specific item
	 *
	 * @return	array
	 * @param	int $itemId		The id of the item to get the info for
	 */
	public function getItem($itemId)
	{
		// redefine
		$itemId = (int) $itemId;

		// return info
		return $this->doAPICall('items/'. $itemId .'/?');
	}


	/**
	 * Retrieve metadata for the user your application is authorized as.
	 *
	 * @return	array
	 */
	public function getMe()
	{
		// return info
		return $this->doAPICall('users/me/?');
	}


	/**
	 * Lists all spot categories
	 *
	 * @return	array
	 */
	public function getSpotCategories()
	{
		// return info
		return $this->doAPICall('categories/?');
	}


	/**
	 * Retrieve information about a specific category
	 *
	 * @param	int $categoryId		The id of the category to get the info for
	 */
	public function getSpotCategoryInfo($categoryId)
	{
		// redefine
		$categoryId = (int) $categoryId;

		// return info
		return $this->doAPICall('categories/'. $categoryId .'/?');
	}


	/**
	 * Retrieve a list of check-ins at a particular spot.
	 *
	 * @return	array
	 * @param	int $spotId		The id of the spot to get the checkins from
	 */
	public function getSpotEvents($spotId)
	{
		// redefine
		$spotId = (int) $spotId;

		// return info
		return $this->doAPICall('spots/'. $spotId .'/events/?');
	}


	/**
	 * Retrieve information about a specific spot
	 *
	 * @return	array
	 * @param 	int $spotId		The spot id to get the information for
	 */
	public function getSpotInfo($spotId)
	{
		// redefine
		$spotId = (int) $spotId;

		// return info
		return $this->doAPICall('spots/'. $spotId .'/?');
	}


	/**
	 * Retrieve a list of items available at a particular spot
	 *
	 * @return	array
	 * @param	int $spotId		The id of the spot to get a list of item from
	 */
	public function getSpotItems($spotId)
	{
		// redefine
		$spotId = (int) $spotId;

		// return items
		return $this->doAPICall('spots/'. $spotId .'/items/?');
	}


	/**
	 * Retrieve a list of spots within a specified distance of a location
	 *
	 * @return	array
	 * @param	float $lat		Latitude
	 * @param	float $lng		Longitude
	 * @param	int $radius		Search radius (in meters)
	 */
	public function getSpotList($lat, $lng, $radius)
	{
		// redefine
		$lat = (float) $lat;
		$lng = (float) $lng;
		$radius = (int) $radius;

		// return the list
		return $this->doAPICall('spots/?', array('lat' => $lat, 'lng' => $lng, 'radius' => $radius));
	}


	/**
	 * Retrieve a list of photos taken at a particular spot
	 *
	 * @return	void
	 * @param	int $spotId		The id of the spot to get a list of photos from
	 */
	public function getSpotPhotos($spotId)
	{
		// redefine
		$spotId = (int) $spotId;

		// return items
		return $this->doAPICall('spots/'. $spotId .'/photos/?');
	}


	/**
	 * Get the timeout
	 *
	 * @return	int
	 */
	public function getTimeout()
	{
		return $this->timeout;
	}


	/**
	 * Retrieve information about a specific trip
	 *
	 * @return	array
	 * @param	int $tripId		The id of the trip to get the info for
	 */
	public function getTrip($tripId)
	{
		// redefine
		$tripId = (int) $tripId;

		// return info
		return $this->doAPICall('/trips/'. $tripId .'/?');
	}


	/**
	 * Retrieve a list of trips
	 *
	 * @return	array
	 */
	public function getTripList()
	{
		// return info
		return $this->doAPICall('/trips/?');
	}


	/**
	 * Retrieve information about a specific user
	 *
	 * @return	array
	 * @param	string $userId		The id of the user (username) to get the info for
	 */
	public function getUser($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
 		return $this->doAPICall('users/'. $userId .'/?');
	}


	/**
	 * Get the user agent
	 *
	 * @return	string
	 */
	public function getUserAgent()
	{
		return (string) 'PHP Gowalla '. self::VERSION .' '. $this->userAgent;
	}


	/**
	 * Retrieve a list of friends
	 *
	 * @return	array
	 * @param	string $userId			The id of the user (username) to get the info for
	 */
	public function getUserFriends($userId)
	{
		// redefine
		$userId = (string) $userId;

		//return info
		return $this->doAPICall('users/'. $userId .'/friends/?');
	}


	/**
	 * Retrieve a list of recent activity of an users friends
	 *
	 * @return	array
	 * @param	int $userId			The id of the user (username) to get the info for
	 */
	public function getUserFriendsActivity($userId)
	{
		// redefine
		$userId = (string) $userId;

		//return info
		return $this->doAPICall('users/'. $userId .'/activity/friends/?');
	}


	/**
	 * Retrieve a list of items a user has
	 *
	 * @return	array
	 * @param	string $userId			The id of the user (username) to get the info for
	 */
	public function getUserItems($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
		return $this->doAPICall('users/'. $userId .'/items/?');
	}


	/**
	 * Retrieve a list of missing items for a user
	 *
	 * @return	array
	 * @param	string $userId			The id of the user (username) to get the info for
	 */
	public function getUserMissingItems($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
		return $this->doAPICall('users/'. $userId .'/items/missing/?');
	}


	/**
	 * Retrieve a list of photos a user has taken
	 *
	 * @return	array
	 * @param	string $userId			The id of the user (username) to get the info for
	 */
	public function getUserPhotos($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
		return $this->doAPICall('users/'. $userId .'/photos/?');
	}


	/**
	 * Retrieve a list of pins a user has
	 *
	 * @return	array
	 * @param	string $userId			The id of the user (username) to get the info for
	 */
	public function getUserPins($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
		return $this->doAPICall('users/'. $userId .'/pins/?');
	}


	/**
	 * Retrieve a list of the stamps the user has collected
	 *
	 * @return	array
	 * @param	string $id				The id of the user (username) to get the info for
	 * @param	int[optional] $limit	Number of results to show
	 */
	public function getUserStamps($userId, $limit = 20)
	{
		// redefine
		$userId = (string) $userId;
		$limit = (int) $limit;

		// return info
		return $this->doAPICall('users/'. $userId .'/stamps/?', array('limit' => $limit));
	}


	/**
	 * Retrieve a list of spots the user has visited most often
	 *
	 * @return	array
	 * @param	string $userId			The id of the user (username) to get the info for
	 */
	public function getUserTopSpots($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
		return $this->doAPICall('users/'. $userId .'/top_spots/?');
	}


	/**
	 * Retrieve a list of vault items for a user
	 *
	 * @return	array
	 * @param	string $userId			The id of the user (username) to get the info for
	 */
	public function getUserVaultItems($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
		return $this->doAPICall('users/'. $userId .'/items/vault/?');
	}


	/**
	 * Retrieve a list of spot URLs the user has visited
	 *
	 * @return	array
	 * @param	string $userId		The id of the user (username) to get the info for
	 */
	public function getUserVisitedSpotURLs($userId)
	{
		// redefine
		$userId = (string) $userId;

		// return info
		return $this->doAPICall('users/'. $userId .'/visited_spots_urls/?');
	}


	/**
	 * Get a new acccess token in exchange for an expired token
	 *
	 * @return	array
	 * @param	string $accessToken		The current access token
	 * @param	string $refreshToken	The refresh token
	 */
	public function refreshToken($accessToken, $refreshToken)
	{
		// redefine
		$accessToken = (string) $accessToken;
		$refreshToken = (string) $refreshToken;

		// build parameters
		$parameters = array('grant_type' => 'refresh_token',
							'refresh_token' => $refreshToken,
							'access_token' => $accessToken,
							'client_id' => $this->clientId,
							'client_secret' => $this->clientSecret);

		// return token information
		return $this->doTokenCall($parameters);
	}


	/**
	 * Request a new token from Gowalla
	 *
	 * @return void
	 */
	public function requestToken($code)
	{
		// check if code param is set
		if(isset($code))
		{
			// code from gowalla
			$code = (string) $code;

			// build parameters
			$parameters = array('grant_type' => 'authorization_code',
		  		  				'client_id' => $this->clientId,
				  				'client_secret' => $this->clientSecret,
								'redirect_uri' => $this->redirectURI,
				  				'code' => $code,
								'scope' => 'read-write');

			// return token information
			return $this->doTokenCall($parameters);
		}

		// code param isn't set
		else throw new GowallaException('Can not get token from Gowalla. No code parameter provided.');
	}


	/**
	 * Set the timeout
	 *
	 * @return	void
	 * @param	int		The timeout
	 */
	public function setTimeOut($timeout)
	{
		$this->timeout = (int) $timeout;
	}


	/**
	 * Set the user agent
	 *
	 * @return	void
	 * @param	string $userAgent	The user agent
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = (string) $userAgent;
	}
}


/**
 * Gowalla Exception class
 *
 * @author	Lester Lievens <lievens.lester@gmail.com>
 */
class GowallaException extends Exception { }

