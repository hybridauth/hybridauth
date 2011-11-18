<?php
//https://github.com/myspace/myspace-php-sdk/blob/master/source/MySpaceID/myspace.php
// modified

class MySpaceException extends Exception {
  const TOKEN_REQUIRED = 1;
  const REMOTE_ERROR   = 2;
  const REQUEST_FAILED = 3;
  const CONNECT_FAILED = 4;

  public $response;
  
  public static $MS_DUMP_REQUESTS = NULL;

  function __construct($msg, $code, $response=null) {
    parent::__construct($msg, $code);
    // $this->response = $response;
    
    // $datetime = new DateTime();
    // $datetime =  $datetime->format(DATE_ATOM);
    
    // file_put_contents(self::$MS_DUMP_REQUESTS, 
	    // "\r\n====================================================\r\n".
	    // "time: $datetime\r\n" .
	    // "message:\r\n--------------------------\r\n$msg\r\n\r\n" .
	    // "code:\r\n----------------------------\r\n$code\r\n\r\n" .
	    // "response:\r\n----------------------------\r\n$response\r\n\r\n",
			// FILE_APPEND);
    
  }
}

class MySpace {

  private $offsite = true;
	
/**
* @access public
*/
  public static $MS_API_ROOT = "http://api.myspace.com";

/**
* @access public
*/
  public static $MS_DUMP_REQUESTS = NULL;
  // set to a pathname to dump out http requests to a log. For example, "./ms.log"

  // OAuth URLs
  public function requestTokenURL() { return self::$MS_API_ROOT.'/request_token'; }
  public function authorizeURL() { return self::$MS_API_ROOT.'/authorize'; }
  public function accessTokenURL() { return self::$MS_API_ROOT.'/access_token'; }


  //new external functions

  /**
   * RESTurl: http://api.myspace.com/v1/user
   * Gets the MySpace UserId of the user represented by the token
   *
   * @return String value extracted from the response
   */
  public function getCurrentUserId(){
  	$REST = "http://api.myspace.com/v1/user".'.json';
  	$params = NULL;

  	$responseJSON = $this->call($REST,$params, 'GET');

  	//parseJSON is a static method and does not effect the object
  	$currentUser = self::parseJSON($responseJSON);
  	return $currentUser->userId;
  }

  /**
   * REST Notes: http://api.myspace.com/v1/users/{userId}/albums
   *
   * Gets the MySpace UserId of the user represented by the tokenReturns the photo
   *  album data for the user specified by userid.
   *
   * Returns the dynamically generated "Albums" "shallow object" that contains
   *  the user's photo album information. The field names of the object will be
   *  identical to those that are returned in a direct REST API call.
   *
   *
   * @param $userId: The user's MySpace user ID
   * @param $page: Specifies the sequential page number starting from 1. If not specified, defaults to 1
   * @param $pageSize: Specifies the number of albumsper page. If not specified, defaults to 20. Setting the pageSize parameter to "all" will give the entire list
   *
   * @return String value extracted from the response
   */
  public function getAlbums($userId, $page=1, $pageSize=20){
  	//TODO:validate($userId)

  	$REST = "http://api.myspace.com/v1/users/".$userId."/albums".'.json';


  	//set the params
  	$params = array( //if $page is null or empty, use the default, else use $page
  				'page' => (empty($page) ? 1 : $page),
  				'pageSize' => (empty($pageSize) ? 20 : $pageSize)
  				);



  	$responseJSON = $this->call($REST,$params, 'GET');

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);
  }

  /**
   * The function will get all of the activites of a given user, provided that the current user and application have privilages to access it
   *
   * @param int|string $userId
   * @param unknown_type $culture
   * @param unknown_type $lastRetrievalTimeStamp
   * @param string $activityTypes 'EventAttending','EventPosting','ProfileSongAdd','FriendAdd','FriendCategoryAdd','ForumPosted','JoinedGroup','ForumTopicReply','ProfileVideoUpdate','FavoriteVideoAdd','PhotoAdd','MobilePhotoUpload','PhotoTagged','BlogAdd','SongUpload','PersonalBandShowUpdate' 
   * @return SimpleXML a SimpleXML representation of an Activities ATOM Feed
   */
  public function getActivities_ATOM($userId, $culture=null, $lastRetrievalTimeStamp=null, $activityTypes=null){
  	//TODO:validate($userId)
  	//TODO: this api call might require the userid to match the access token.
  	//see getFriendsActivities_ATOM for activity types

  	$REST = "http://api.myspace.com/v1/users/".$userId."/activities.atom";
	//$REST = "http://api.myspace.com/v1/users/".$userId."/activities".'.atom';


  	//set the params
  	$params = array( 'culture' => null,
  					 'lastRetrievalTimeStamp' => null,
  					 'activitytypes' => $activityTypes
  					);

  	$responseATOM = $this->call_ATOM($REST,$params, 'GET');

  	//returns a SimpleXML Atom feed.
  	return $responseATOM;
  }
  
  /**
   * The function will get all of the activites of a given user's friends, provided that the current user and application have privilages to access it
   *
   * @param int|string $userId
   * @param unknown_type $culture
   * @param unknown_type $lastRetrievalTimeStamp
   * @param string $activityTypes 'EventAttending','EventPosting','ProfileSongAdd','FriendAdd','FriendCategoryAdd','ForumPosted','JoinedGroup','ForumTopicReply','ProfileVideoUpdate','FavoriteVideoAdd','PhotoAdd','MobilePhotoUpload','PhotoTagged','BlogAdd','SongUpload','PersonalBandShowUpdate' 
   * @return SimpleXML a SimpleXML representation of an Activities ATOM Feed
   */
  public function getFriendsActivities_ATOM($userId, $culture=null, $lastRetrievalTimeStamp=null, $activityTypes=null){
  	//TODO:validate($userId)
  	//TODO: this api call might require the userid to match the access token.

  	$REST = "http://api.myspace.com/v1/users/".$userId."/friends/activities.atom";
	

  	//culture, lastRetrievalTimeStamp(optional), activitytypes(optional)
  	//set the params
  	$params = array( 'culture' => null,
  					 'lastRetrievalTimeStamp' => null,
  					 'activitytypes' => $activityTypes
  					);

	$responseATOM = $this->call_ATOM($REST,$params, 'GET');


  	//returns a SimpleXML Atom feed.
  	return $responseATOM;
  }

 


  /**
   * retrieves all photos for a user's album
   *
   * @param String $userId
   * @param String $albumId
   * @return object a php object representing the JSON
   */
  public function getAlbum($userId, $albumId){
  	//TODO: validate($userId, $albumId)

  	$REST = "http://api.myspace.com/v1/users/".$userId."/albums/".$albumId."/photos".'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }
  
  /**
  *
  * @link http://wiki.developer.myspace.com/index.php?title=POST_v1_users_userId_albums
  *
  * @param string $userId		the userId of the current user.
  * @param string $tile			'the title of the album;
  * @param string $location		the location of the album, either geography or some other tag
  * @param string $privacy		'Everyone' | 'FriendsOnly' | 'Me'
  *
  * @return object a PHP object representative of the JSON response
  */
  public function createAlbum($userId, $title, $location=null, $privacy='Everyone'){
  
	  $method = 'POST';
	  $REST = 'http://api.myspace.com/v1/users/'.$userId.'/albums'.'json';
	  
	  $privacyValid = array('Everyone','FriendsOnly','Me');
	  $isPrivacyValid = false;
	  foreach($privacyValid as $test){
		  if($test == $privacy) $isPrivacyValid = true;
	  }
	  if($isPrivacyValid == false) {
		  //raise error
	  }
	  
	  $body = array(
		  'location'=> $location,
		  'title' => $title,
		  'privacy' => $privacy
		  );
	  
	  $response = $this->makeOAuthRequest(
		  						$REST,
								null,
								$method,
								array('Content-Type' => 'application/x-www-form-urlencoded'),
								$body
								);
	  
	  if($response['status']== 200|201 ){
		  //lets assume that is JSON since that is what we asked for.
		  return self::parseJSON($reponse['body']);
	  }
	  return false;
  }

  /**
   * Enter description here...
   *
   * @param String $userId
   * @param int $page
   * @param int $pageSize
   * @param String $list 'top,online,app' must select top, online or app, top is the default response
   * @param String $show 'mood|status|online' can set some combintion there of via vertical pipes '|'
   * @return object a php object representing the JSON which is list of the current user's friends
   */
  public function getFriends(
  					$userId,
  					$page=1,
  					$pageSize=20,
  					$list=NULL,
  					$show='mood|status|online'){

  	$REST = "http://api.myspace.com/v1/users/".$userId."/friends".'.json';

  	//set the params
  	$params = array(
  				'page' => (empty($page) ? 1 : $page), //if $page is null or empty, use the default, else use $page
  				'page_size' => (empty($pageSize) ? 20 : $pageSize),
  				'list' => $list,
  				'show' => $show
  				);

  	$responseJSON = $this->call($REST, $params, 'GET');

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);


  }

  /**
   * describes if $userId and $friendsId are currently friends
   *
   * @param String $userId
   * @param int $friendsId
   * @return object an object with a boolean that describes if $userId and $friendsId are currently friends
   */
  public function getFriendship($userId, $friendsId){
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/friends/'.$friendsId.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }

  /**
   * gets the mood of the current user
   *
   * @param String $userId
   * @return object the mood of the current user
   */
  public function getMood($userId){
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/mood'.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }

  /**
   * gets a list of all of the current users photos
   *
   * @param String $userId
   * @param int $page
   * @param int $pageSize
   * @return object a list containing all of the current users photos
   */
  public function getPhotos( $userId, $page=1, $pageSize=20){

  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/photos'.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }

  /**
   * gets the meta data for a specific photoid
   *
   * @param String $userId
   * @param String $photoId
   * @return object the meta data for a specific photoid
   */
  public function getPhoto( $userId, $photoId){
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/photos/'.$photoId.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }

  /**
   * gets a user's profile, the object type changes depending on the detail type
   * you are better off useing getProfileBasic(), getProfileFull(), or getProfileExtended()
   *
   * @param String $userId
   * @param String $detailtype 'full' or 'basic' or 'extended' determines request and return type
   * @return mixed a users profile, which can be of type ProfileFull, ProfileBasic, or ProfileExtended
   */
  public function getProfile( $userId, $detailtype = 'full' ){
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/profile'.'.json';
	
	//default to full, TODO: maybe should raise error?
	$detailType = ($detailtype == 'full'||'extended'||'basic') ? $detailtype : 'full';
	
	//requires a GET request, POST does not work
  	$responseJSON = $this->call($REST, array('detailtype' => $detailType), 'GET' );

	//requires a GET request, POST does not work

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }
  /**
  * gets a basic user profile given the userid
  * @param string $userId
  * @return ProfileBasic
  */
  public function getProfileBasic( $userId ){ $this->getProfile($userId, 'basic'); }
  /**
  * gets a full user profile given the userid
  * @param string $userId
  * @return ProfileFull
  */
  public function getProfileFull( $userId ){ $this->getProfile($userId, 'full'); }
  /**
  * gets an extended user profile given a userid
  * @param string $userId
  * @return ProfileExtended
  */
  public function getProfileExtended( $userId ){ $this->getProfile($userId, 'extended'); }

  /**
   * gets a user's status
   *
   * @param String $userId
   * @return object a php object that represents the JSON
   */
  public function getStatus( $userId ){
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/status'.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }
  /**
  * Updates/ Adds a User's status
  * 
  * @param string $userId the userId of the current user or one of their friends
  * @param string $newStatus	the new status to post to the users profile
  * @return boolean returns true if the server response is a 200 or 201, else it returns false
  */
  public function updateStatus($userId, $newStatus){
	//this is done on an XML endpoint because .json might return a 405 error
	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/status';
	$body = array('status' => $newStatus);
  	return $this->doPut($REST, $body, 'PUT');
  }
  
  /**
   * gets a list of the current users videos
   *
   * @param String $userId
   * @return object
   */
  public function getVideos( $userId ){
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/videos'.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }

  /**
   * gets the meta data for a specific userid, and videoid
   *
   * @param String $userId
   * @param String $videoId
   * @return object meta data about a specific video
   */
  public function getVideo( $userId, $videoId ){
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/videos/'.$videoId.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);

  }

  /**
   * Gets info about an album.
   * @param String $userId
   * @param String $albumId
   * @return object meta data about an album
   */
  public function getAlbumInfo($userId, $albumId) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/albums/'.$albumId.'.json';

  	$responseJSON = $this->call($REST);

  	//parseJSON is a static method and does not effect the object
  	return self::parseJSON($responseJSON);
  }
  
  /**
   * Gets info about a photo in an album.
   * @param String $userId
   * @param String $albumId
   * @param String $photoId
   * @return object meta data about a photo in an album
   */
  public function getAlbumPhoto($userId, $albumId, $photoId) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/albums/'.$albumId.'/photos/'.$photoId.'.json'; 
 	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }
  
	/**
	 * Posts a notification to a list of recipients.  At most 1000 recipients can be specified.  You will need to pass in 
	 * a template, which specifies the text in the notification, the buttons, and where the buttons link to.  
	 * @param $appId Id of app
	 * @param $recipients A comma-separated list of recipients.
	 * @param $templateParameters Parameters defining the template for the notification.  This is a Map.  Possible key values are:
	 *        <ol>  
	 *        <li> content (required) - Text content of the notification
	 *        <li> button0_surface (optional) - where button 0 should link to: "canvas" or "appProfile"
	 *        <li> button0_label (optional) - text label on button 0
	 *        <li> button1_surface (optional) - where button 1 should link to: "canvas" or "appProfile"
	 *        <li> button1_label (optional) - text label on button 1
	 *        </ol>
	 * @param $mediaItems A URI to a MySpace image, either a profile image or an album photo. External images are not allowed. 
	 *        At this time, only one media item is supported. (optional; pass null to not specify).
	 */
  public function sendNotification($appId, $recipients, $templateParams, $mediaItems) {
	if ($templateParams['content'] == null)
		throw new MySpaceException('\'content\' key required in templateParameters Map');

	// Convert templateParameters to a string representation
	$sb = '{';
	$n = count($templateParams);
	$i = 0;
 	foreach ($templateParams as $key => $val) {
 		$sb .= '"' . $key . '":"' . $val . '"';
 		$i++;
 		if ($i == $n)
 			$sb .= '}';
 		else
 			$sb .= ',';
	} 
	
	// Put mediaItems in braces, as required by the REST API
	$mediaItems = '{"' . $mediaItems . '"}';

	// Send request
	$appParams = array("recipients" => $recipients, "templateParameters" => $sb, "mediaItems" => $mediaItems);

	$REST = 'http://api.myspace.com/v1/applications/'.$appId.'/notifications';
  	return $this->doPut($REST, $appParams, 'POST');
  }
  
  /**
   * Clears app data for the given user id.
   * @param string $userId the user whose app data is to be cleared
   * @param string $keys semicolon separated keys to clear
   * @return true on success, false on failure
   */
  public function clearAppData($userId, $keys = null) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/appdata/'.$keys;
  	return $this->doPut($REST, array(), 'DELETE');
  }
  
  /**
   * Stores app data for the given user id.
   * @param string $userId the user whose app data is to be cleared
   * @param hash $dataHash hash mapping keys to data
   * @return true on success, false on failure
   */
  public function putAppData($userId, $dataHash) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/appdata';
  	return $this->doPut($REST, $dataHash, 'PUT');
  }
  
  /**
   * Fetches app data of the specified keys for the given user id.
   * @param string $userId the user whose app data is to be fetched
   * @param string $keys semicolon separated keys to fetch
   * @return App data requested
   */
  public function getAppData($userId, $keys = null) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/appdata';
  	if ($keys != null)
  		$REST .= '/'.$keys;
  	$REST .= '.json';
 	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }
  
  /**
   * Puts global app data 
   * @param hash $dataHash Hash of app data to put
   */
  public function putGlobalAppData($dataHash) {
  	$REST = 'http://api.myspace.com/v1/appdata/global';
  	return $this->doPut($REST, $dataHash, 'PUT');
  }
  
  /**
   * Gets global app data
   * @param string keys Semicolon-separated keys
   * @return 
   */
  public function getGlobalAppData($keys) {
  	$REST = 'http://api.myspace.com/v1/appdata/global';
  	if ($keys != null)
  		$REST .= '/'.$keys;
  	$REST .= '.json';
   	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }
  
  /**
   * Clears global app data identified by given keys
   * @param $keys Semicolon-separated keys
   */
  public function clearGlobalAppData($keys) {
  	$REST = 'http://api.myspace.com/v1/appdata/global/' . $keys;
  	return $this->doPut($REST, null, 'DELETE');
  }
  
  /**
   * Fetches app data of the specified keys for the friends of the given user id.
   * @param string $userId the user whose friends' app data you want
   * @param string $keys semicolon separated keys to fetch
   * @return App data requested
   */
    public function getUserFriendsAppData($userId, $keys = null) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/friends/appdata';
  	if ($keys != null)
  		$REST .= '/'.$keys;
  	$REST .= '.json';
 	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }
  
  /**
   * Fetches status for the friends of the given user id.
   * @param string $userId the user whose friends' status you want
   * @return Status of friends
   */
  public function getFriendsStatus($userId) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/friends/status.json';
 	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }
  
  /**
   * Fetches status for the friends of the given user id.
   * @param string $userId the user whose friends' status you want
   * @return Status of friends
   */
  public function getUserStatus($userId) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/status.json';
 	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }
  
  /**
   * Fetches status history for the user of the given user id.
   * @param string $userId the user whose status history you want
   * @return Status history of user
   */
  public function getStatusHistory($userId) {
	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/activities.atom';
	
 	//requires a GET request, POST does not work
  	$response = $this->call_ATOM($REST, array('activityTypes' => 'StatusMoodUpdate'), 'GET' );

	//requires a GET request, POST does not work

  	//parseJSON is a static method and does not effect the object
  	return $response;
  }
  
  /**
   * Gets preference of a user
   * This is for onsite OpenSocial apps only
   * @param string $userId id of user 
   * @return Preference of user
   */
  public function getPreferences($userId) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/preferences.json';
  	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }
  
  /**
   * Gets user indicators
   * @param string $userId id of user 
   * @return User indicators
   */
  public function getIndicators($userId) {
  	$REST = 'http://api.myspace.com/v1/users/'.$userId.'/indicators.json';
  	$responseJSON = $this->call($REST);
  	return self::parseJSON($responseJSON);
  }

  /**
   * Portable contacts call to get person.  
   * @param string $fields Fields to retrieve
   * @return Person being fetched
   */
  public function getPersonPoco($fields = null) {
  	$REST = 'http://api.myspace.com/v2/people/@me/@self';

  	$params = array('format'=>'json', 'fields'=>'@all', 'fields'=>$fields);
  	$responseJSON = $this->call($REST, $params, 'GET');

  	return self::parseJSON($responseJSON);
  }
  
  
  /**
   * Portable contacts call to get friends.  
   * @param string $startIndex First item to retrieve; defaults to 1 if not given
   * @param string $count Numer of items to retrieve; defaults to 10 if not given
   * @return Friends being fetched
   */
  public function getFriendsPoco($startIndex = 1, $count = 10) {
  	$REST = 'http://api.myspace.com/v2/people/@me/@friends';

    $params = array('format'=>'json', 'startIndex'=>$startIndex, 'count'=>$count);
  	$responseJSON = $this->call($REST, $params, 'GET');

  	return self::parseJSON($responseJSON);
  }
  
  
  
  // internal 'private' functions

  /**
   * Sets up the MySpaceID API with your credentials
   *
   * @param String $consumerKey
   * @param String $consumerSecret
   * @param String $oAuthToken
   * @param String $oAuthTokenSecret
   * @return object a new myspace object
   */
  public function __construct($consumerKey,
		       $consumerSecret,
		       $oAuthToken = null,
		       $oAuthTokenSecret = null,
		       $isOffsite = true,
		       $authorized_verifier = '')  {
  
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumerKey, $consumerSecret, NULL);
    $this->offsite = $isOffsite;
    $this->authorized_verifier = $authorized_verifier;
   
    if (!empty($oAuthToken)) {
    	$this->token = new OAuthConsumer($oAuthToken, $oAuthTokenSecret);
    } else {
      $this->token = NULL;
    }
  }

    /**
   * Get a request token for authenticating your application with FE.
   *
   * @return a key/value pair array containing: oauth_token and
   * oauth_token_secret.
   */
  public function getRequestToken($callbackUrl) {
	  /**
	  *@link http://oauth.net/core/1.0/#http_codes
	  * HTTP 400 Bad Request
	  	Unsupported parameter
		Unsupported signature method
		Missing required parameter
		Duplicated OAuth Protocol Parameter
	   * HTTP 401 Unauthorized
	   	Invalid Consumer Key
		Invalid / expired Token
		Invalid signature
		Invalid / used nonce
	  */
	  
	  /*consumer key is not set!! or is wrong
	  Fatal error: Uncaught exception 'MySpaceException' with message 'Request to http://api.myspace.com/request_token?oauth_version=1.0&oauth_nonce=9a5139f46cb96314a7d5a3faf6ee95ca&oauth_timestamp=1238041272&oauth_consumer_key=NOT%20SET&oauth_signature_method=HMAC-SHA1&oauth_signature=8RqzekFfhFmAsXAql3RHJTX9A%2Fc%3D failed:<br/><br/> HTTP error 401 <br/><br/> Response:<br/><br/> HTTP/1.1 401 oauth_problem=consumer_key_unknown Date: Thu, 26 Mar 2009 04:21:12 GMT Server: Microsoft-IIS/6.0 WWW-Authenticate: OAuth realm="http://api.myspace.com/authorization", oauth_problem=consumer_key_unknown Set-Cookie: MSCulture=IP=208.113.234.71&IPCulture=en-US&PreferredCulture=en-US&PreferredCulturePending=&Country=VVM=&ForcedExpiration=633736128723757790&timeZone=0&myStuffDma=&USRLOC=QXJlYUNvZGU9NzE0JkNpdHk9QnJlYSZDb3VudHJ5Q29kZT1VUyZDb3VudHJ5TmFtZT1Vbml0ZWQgU3RhdGVzJkRtYUNvZGU9ODAzJkxhdGl0dWRlPTMzLjkyNjkmTG9uZ2l0dWRlPS0xMTcuODYxMiZQb3N0YWxDb2RlPTkyODIxJlJlZ2lvbk5hbWU9Q0E=; domain=.myspace.com; expires=Sat, 25-Apr-2009 04:21:12 GMT in /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php on line 1197
	  */
	
  	if ($callbackUrl == "" || !isset($callbackUrl))$callbackUrl="oob";
  	
    $r = $this->oAuthRequest($this->requestTokenURL(), array("oauth_callback"=>$callbackUrl));

    $token = $this->oAuthParseResponse($r);

    $this->token = new OAuthConsumer(
    	$token['oauth_token'], 
    	$token['oauth_token_secret'], 
    	$callbackUrl, 
    	$token['oauth_callback_confirmed']); // use this token from now on

    if (self::$MS_DUMP_REQUESTS){
    	self::dump(
    				"Now the user is redirected to ".$this->getAuthorizeURL($token['oauth_token']).
    				"\nOnce the user returns, via the callback URL for web authentication".
    				" or manually for desktop authentication, we can get their access token".
    				" and secret by calling /oauth/access_token.".
    				"\n\n");
    }
    return $token;
  }




  /**
   * Get the URL to redirect to to authorize the user and validate a
   * request token.
   *
   * @returns a string containing the URL to redirect to.
   */
  public function getAuthorizeURL($token) {
    // $token can be a string, or an array in the format returned by getRequestToken().
    if (is_array($token)) $token = $token['oauth_token'];
	
	return 	(
		$this->authorizeURL() .
		'?oauth_token=' .
		$token .
		'&oauth_callback=http://' .
		$_SERVER['HTTP_HOST'] .
		($_SERVER['PORT'] == '80' ? '' : (':' . $_SERVER['PORT'])) .
		$_SERVER['SCRIPT_NAME'] .
		'?f=callback'
	);
  }



   /**
   * Exchange the request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @param RequestToken $token
   * @return array("oauth_token" => the access token,
   *                "oauth_token_secret" => the access secret)
   */
  public function getAccessToken($token=NULL) {
    $this->requireToken();
    
    //$r = $this->oAuthRequest($this->accessTokenURL());

    //oauth_verifier required for 1.0a
    $r = $this->oAuthRequest($this->accessTokenURL(), array(
    	"oauth_verifier"=> $this->authorized_verifier));
    
    $token = $this->oAuthParseResponse($r);

    $this->token = new OAuthConsumer(
    					$token['oauth_token'],
    					$token['oauth_token_secret']);
    					// use this token from now on

    return $this->token;
  }



  /**
   * the entry point function to call the REST API
   *
   *
   * @param		$url
   * @param		$params
   * @param 	$request_method
   *
   * @return	Object a PHP representation of the returned JSON, XML, or ATOM feed.
   * */
  public function call($url, $params=array(), $request_method=NULL) {


    return $this->call_JSON($url, $params, $request_method);
  }

  /**
   *
   *
   * @param		$url
   * @param		$params
   * @param 	$request_method
   *
   * @return	Object a PHP representation of the returned JSON, XML, or ATOM feed.
   * */
  protected function call_JSON($url, $params=array(), $request_method=NULL) {
    $this->requireToken();
    $r = $this->oAuthRequest($url, $params, $request_method);
    return $this->parseJSON($r);
  }

/**
   *
   *
   * @param		$url
   * @param		$params
   * @param 	$request_method
   *
   * @return	Object a PHP representation of the returned JSON, XML, or ATOM feed.
   * */
  protected function call_XML($url, $params=array(), $request_method=NULL) {
    $this->requireToken();
    $r = $this->oAuthRequest($url, $params, $request_method);
    return new SimpleXMLElement($r);
  }

  /**
   *
   *
   * @param		$url
   * @param		$params
   * @param 	$request_method
   *
   * @return	Object a PHP representation of the returned JSON, XML, or ATOM feed.
   * */
  protected function call_ATOM($url, $params=array(), $request_method=NULL) {
    $this->requireToken();
    $r = $this->oAuthRequest($url, $params, $request_method);
    return new SimpleXMLElement(mb_convert_encoding($r,'UTF-8', " UTF-8, ASCII, ISO-8859-1, EUC-JP,  SJIS, JIS"));
  }

  /**
   *
   * @param $json a java script object
   *
   * @return Object - a PHP object that represents the JSON
   * */
  protected function parseJSON($json) {
  	if(gettype($json)=="object"){
  		return $json;
  	}

    $r = json_decode($json);

//    if (empty($r)) {
//    	throw new MySpaceException("Empty JSON response",
//    								MySpaceException::REQUEST_FAILED);
//    }

    if (isset($r->rsp) && $r->rsp->stat != 'ok') {
	    throw new MySpaceException(
    								$r->rsp->code.": ".$r->rsp->message,
    								MySpaceException::REMOTE_ERROR,
    								$r->rsp
    							);
    }
    return $r;
  }
  
  /**
  * based on the content type different types are returned
  * @param 	string 	$contentType
  * @param 	mixed 	$data
  * @return mixed
  */
  private function parseResponse($data, $contentType){
	  switch(strtolower(trim($contentType)))
	  {
	  case 'application/x-www-form-urlencoded':
		  return OAuthUtil::decodeUrlEncodedArray($data);
		  break;
	  case 'application/json':
		  return self::parseJSON($data);
		  break;
	  case 'application/xml':
		  return new SimpleXML($data);
		  break;
	  case 'application/xml+atom':
		  return new SimpleXML($data);
		  break;
	  case 'text/html':
		  /*
		  Fatal error: Uncaught exception 'MySpaceException' with message 'Requested --> http://api.myspace.com/v1/users/36452044/status.json Response:<br/><br/> <br/><br/> :: contentType ::\r\ntext/html :: status ::\r\n405 :: body ::\r\n :: headers ::\r\n{"statusCode":"405","statusDescription":null} ' in /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php:772 Stack trace: #0 /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php(380): MySpace->makeOAuthRequest('http://api.mysp...', NULL, 'PUT', Array, Array) #1 /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/samples/myspaceid-openid-oauth/finish_auth.php(58): MySpace->updateStatus(36452044) #2 /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/samples/myspaceid-openid-oauth/finish_auth.php(68): run() #3 {main} thrown in /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php on line 772
		  */
		  return (string)$data;
		  break;
	  default:
		  //we do not know what type it is
		  return (string)$data;
		  //break;
	  }
  }

  /**
   * Does a put to send data to MySpace servers.
   */
  public function doPut($REST, $body, $method) {
	  $response = $this->makeOAuthRequest(
		  						$REST,
								null,
								$method,
								array('Content-Type' => 'application/x-www-form-urlencoded'),
								$body
								);
	  if($response['status']== 200|201 ){
		  return true;
	  }
	  return false;
  }
  
  /**
  * creates a properly formated body
  *@param mixed $body			the body that you want formated for the given content type
  *@param string $contentType	the content type to PUT or POST in the body
  */
  private function formatBody($body, $contentType='application/x-www-form-urlencoded'){
	  if(!empty($body) && $contentType == 'application/x-www-form-urlencoded'){
		  if( is_array($body) ){
			  //create 'application/x-www-form-urlencoded' string
			  $bodyContent = OAuthUtil::encodeUrlEncodedArray($body);
			  return $bodyContent;
			  
		  }elseif( is_string($body) ){
			  //validate $body as 'application/x-www-form-urlencoded' string
			  return $body;
			  
		  }elseif( is_object($body) ){
			  //not all objects can be converted
			  
		  }else{
			  //content type and $body are not compatible
			  
		  }
	  }
	  
	  return NULL;
  }

  

  /** Parse a URL-encoded OAuth response
   * @param 	$responseString
   * @return 	Hash Map
   * */
  protected function oAuthParseResponse($responseString) {
    $r = array();
    foreach (explode('&', $responseString) as $param) {

      $pair = explode('=', $param, 2);

      if (count($pair) != 2) continue;

      $r[urldecode($pair[0])] = urldecode($pair[1]);

    }
    return $r;
  }
  
  /** Format and sign an OAuth / API request
  *	add support/ error handeling for all error types
  *	this does not support multipart bodies
  *   returns a response object
  *@param string $url 		the url of the API REST resource
  *@param string $qParams	the non-oauth (optional) query parameters
  *@param string $method	the HTTP Request method/ verb (GET, POST, PUT, DELETE, etc...)
  *@param string $headers	optional additional headers can be used to set the Content-Type of the body
  *@param string|array $body	a string or array with content that should be sent in the body of the request
  *
  *@return responseObject
  */
  protected function makeOAuthRequest(
	  $url, 
	  $qParams=array(), 
	  $method,
	  $headers=array('Content-Type'=> 'application/x-www-form-urlencoded'),
	  $body=NULL){
  
  	$datetime = new DateTime();
    $datetime =  $datetime->format(DATE_ATOM);
	
  	  if (self::$MS_DUMP_REQUESTS) {
		  $dump = "\r\n";
		  $dump .= '::makeOAuthRequest::@'.$datetime."\r\n";
		  $dump .= "_____________________________________________\r\n";
		  $dump .= '::reqUrl::\'' . $method . '\'  '.$url."\r\n";
		  $dump .= '::reqParams::' . OAuthUtil::encodeUrlEncodedArray( $qParams ). "\r\n";
		  $dump .= '::headers::'."\r\n";
		  foreach($headers as $key => $value){
			  $dump .= $key . ': ' . $value . "\r\n";
		  }
		  $dump .= "\r\n";
		  self::dump($dump);
	  }
  
	  //validate $url
	  //validate $qParams
	  //validate $method
	  if(!self::isSupportedMethod($method)){
		  //raise error, unsupported request method
	  }
	  
	  //validate $headers
	  //get BodyContentType
	  
	  if (self::$MS_DUMP_REQUESTS) {
		  $dump = '::BODY::'."\r\n";
		  if(is_array($body)){
			  $dump .= OAuthUtil::encodeUrlEncodedArray($body) . "\r\n\r\n";
		  }else{
			  $dump .= $body . "\r\n\r\n";
		  }
		  self::dump($dump);
	  }
	  
	  $bodyContentType = $headers['Content-Type'];
	  if(empty($bodyContentType)){
		 //raise error, Content-Type is not set, or may not have the propper casing
	  }
	  
	  if(!self::isSupportedRequestContentType($bodyContentType)){
		  //raise error
	  }
	  
	  /*
	  //formats the body based on its type and the content type
	  we are going to send the body as an array of params
	  $bodyContent = self::formatBody($body, $bodyContentType);
	  */
	  
	  if(!is_array($body)){
		  //right now we want to make sure the body can be signed properly and this requires we process the body as an array of prams
	  }
	  /*
	  if (self::$MS_DUMP_REQUESTS) {
		  $dump = '::BODY CONTENT::'."\r\n";
		  $dump .= $bodyContent . "\r\n\r\n";
		  self::dump($dump);
		  
		 
	  }
	  */
	  //construct the request object
	  $req = OAuthRequest::from_consumer_and_token(
	    				$this->consumer,
	    				$this->token,
	    				$method,
	    				$url,
	    				$qParams,
						$headers,
						$body);
	  
	  //passes a reference to the SHA1-Signing Class to sign the request
	  $req->sign_request($this->sha1_method, $this->consumer, $this->token);
	  
	  
	  
	  //any query params should be in the url aready
	  //any post data should be in the body already
	  $myspace_response = $this->makeHttpRequest(
											$req->get_normalized_http_method(),
											$req->to_auth_url(),
											null,
											$req->get_custom_headers(),
											$req->to_nonAuth_postdata()
											);
	  /*
	  if(!self::isSupportedResponseContentType($myspace_response['content_type')){
	  
	  }*/
	  
	  //dump response
	  $datetime = new DateTime();
	  $datetime =  $datetime->format(DATE_ATOM);
	  if (self::$MS_DUMP_REQUESTS) {
		  $dump = "\r\n\r\n".'::myspace response::@' . $datetime . "\r\n";
		  $dump .= "_____________________________________________\r\n";
		  foreach($myspace_response as $key => $value){
			  $dump .= 	':: ' . $key . ' :: '."\r\n" 
						. $value . "\r\n\r\n";
		  }
		  self::dump($dump);
	  }
	  
	  //i should probably validate the response and check for error messages
	  
	  switch((int)$myspace_response['status'])
	  {
	  	  case 200:
			  //there is not a break line because we want 200's and 201's to act the same.
			  return $myspace_response;
	  	  case 201:
			  //200 success
			  //201 success on put
			  return $myspace_response;
			  
			  break;
		  case 401:
			  /*consumer key is not set!! or is wrong
	  Fatal error: Uncaught exception 'MySpaceException' with message 'Request to http://api.myspace.com/request_token?oauth_version=1.0&oauth_nonce=9a5139f46cb96314a7d5a3faf6ee95ca&oauth_timestamp=1238041272&oauth_consumer_key=NOT%20SET&oauth_signature_method=HMAC-SHA1&oauth_signature=8RqzekFfhFmAsXAql3RHJTX9A%2Fc%3D failed:<br/><br/> HTTP error 401 <br/><br/> Response:<br/><br/> HTTP/1.1 401 oauth_problem=consumer_key_unknown Date: Thu, 26 Mar 2009 04:21:12 GMT Server: Microsoft-IIS/6.0 WWW-Authenticate: OAuth realm="http://api.myspace.com/authorization", oauth_problem=consumer_key_unknown Set-Cookie: MSCulture=IP=208.113.234.71&IPCulture=en-US&PreferredCulture=en-US&PreferredCulturePending=&Country=VVM=&ForcedExpiration=633736128723757790&timeZone=0&myStuffDma=&USRLOC=QXJlYUNvZGU9NzE0JkNpdHk9QnJlYSZDb3VudHJ5Q29kZT1VUyZDb3VudHJ5TmFtZT1Vbml0ZWQgU3RhdGVzJkRtYUNvZGU9ODAzJkxhdGl0dWRlPTMzLjkyNjkmTG9uZ2l0dWRlPS0xMTcuODYxMiZQb3N0YWxDb2RlPTkyODIxJlJlZ2lvbk5hbWU9Q0E=; domain=.myspace.com; expires=Sat, 25-Apr-2009 04:21:12 GMT in /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php on line 1197
	  */
	  
	  /*
	  Fatal error: Uncaught exception 'MySpaceException' with message 'Requested --> http://api.myspace.com/v1/users/36452044/status Response:<br/><br/> <br/><br/> :: contentType ::\r\napplication/xml :: status ::\r\n401 :: body ::\r\n :: headers ::\r\n<error xmlns="api-v1.myspace.com"><statuscode>401</statuscode><statusdescription>Authentication failed. Failed to resolve application URI "01d4153357314a9da0c02f8e4c1270ae,01d4153357314a9da0c02f8e4c1270ae"</statusdescription></error> ' in /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php:772 Stack trace: #0 /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php(380): MySpace->makeOAuthRequest('http://api.mysp...', NULL, 'PUT', Array, Array) #1 /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/samples/myspaceid-openid-oauth/finish_auth.php(58): MySpace->updateStatus(36452044) #2 /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/samples/myspaceid-openid-oau in /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php on line 772
	  */
			  //suspended app
			  //insuffecient app permission
			  //insuffecient user or application permission
			  //incorrect user
			  //missing or revoked token
			  //insuffecient OpenCanvas Permissions
			  //user has not added app
			  //expired timestamp
			  break;
		  case 403:
			  //missing oauth params
			  //expired timestamp
			  //used nonce
			  //invalid key
			  //invalid token
			  break;
		  case 404:
			  //the resource is not found
			  //missing user
			  break;
		  case 411:
			  //missing content length
			  break;
		  case 500:
		  	  //internal server error
			  break;
	  }
	  
	  //if we are still here we did not handle the error
	  
	  throw new MySpaceException(
		  			"Requested --> $url \r\n" . 
					"Response:<br/><br/>\r\n\r\n".
	      			"<br/><br/>\r\n".
					$dump, 
					MySpaceException::REQUEST_FAILED);
  }
  
  
  /**
  *  this function will make a raw HTTP requests using PHP's cURL
  *
  *  //maybe? make* functions should always be called with a try(){}catch(){}
  */
  private function makeHttpRequest(
	  $method, 
	  $url, 
	  $qParams, 
	  $headers=array(), 
	  $bodyContent=NULL
	  ){
  	  
  
  	$datetime = new DateTime();
    $datetime =  $datetime->format(DATE_ATOM);
  
  	  if (self::$MS_DUMP_REQUESTS) {
		  $dump = "\r\n";
		  $dump .= '::makeHttpRequest::@'.$datetime."\r\n";
		  $dump .= "_____________________________________________\r\n";
		  $dump .= '::reqUrl::\'' . $method . '\'  ' . $url . "\r\n";
		  $dump .= '::reqParams::' . OAuthUtil::encodeUrlEncodedArray( $qParams ) . "\r\n";
		  $dump .= '::custom headers::'."\r\n";
		  foreach($headers as $key => $value){
			  $dump .= $key . ': ' . $value . "\r\n";
		  }
		  $dump .= "\r\n";
		  $dump .= '::bodyContent::'."\r\n";
		  $dump .= $bodyContent;
		  self::dump($dump);
	  }
  
  //this url, may or may not have signed params on it already,
  //if it does, we may not want more params
  
  //hmmm what about the body content being signed?
  	  
  	  if(!self::isSupportedMethod($method)){
		  //raise error, unsupported request method
	  }
	  
	  //not sure if this is needed yet
	  $url_bits = parse_url($url);
	  $req_url = $url_bits['path'];
	  
	  if(empty($bodycontent)){
		  //if we are doing a GET, the query params need to be in the request url
		  //maybe for DELETE too?
		  //still not sure about this
		  if ($url_bits['query']) $req_url .= '?' . $url_bits['query'];
	  }
	  
	  //init curl
	  $ch = curl_init();
	  
	  /**
	  *@link http://us.php.net/manual/en/function.curl-setopt.php
	  */
	  //something related to CURLOP_SSL_VERIFYPEER, and validating certs from local a local path
	  if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
	  
	  //sets the url we are going to make a request from
	  //this is the full $url from the function call
	  curl_setopt($ch, CURLOPT_URL, $url);
	  
	  //The number of seconds to wait whilst trying to connect. Use 0 to wait indefinitely
	  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	  
	  //The maximum number of seconds to allow cURL functions to execute.
	  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	  
	  //TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  
	  //setup the headers
	  $h =  array();
	  foreach($headers as $k => $v){
		  $h[] = $k . ": " . $v;
	  }
	  
	  curl_setopt($ch, CURLOPT_HEADER, true);
	  
	  //ok, now we need to worry about POST, and PUTs
	  switch($method){
	  	case 'GET':
			//we do not need to do anything
			break;
		case 'POST':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			//The full data to post in a HTTP "POST" operation. To post a file, prepend a filename with @ and use the full path. This can either be passed as a urlencoded string like 'para1=val1&para2=val2&...' or as an array with the field name as key and field data as value.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyContent);
			//does this work with UTF-8 chars?
			//$h[] = 'Content-Length: ' . strlen($bodyContent);
			break;
		case 'PUT':
			//if we are going to put a file, 
			//this needs to be done differently
			//this is designed for small url encoded strings
			
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			//The full data to post in a HTTP "POST" operation. To post a file, prepend a filename with @ and use the full path. This can either be passed as a urlencoded string like 'para1=val1&para2=val2&...' or as an array with the field name as key and field data as value.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyContent);
			//$h[] = 'Content-Length: ' . strlen($bodyContent);
			break;
		case 'DELETE':
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			break;
	  }
	  
	  self::dump("\r\n" . '::Sent Headers::' . "\r\n" . implode("\r\n", $h));
	  curl_setopt($ch, CURLOPT_HTTPHEADER, array( implode("\r\n", $h) ) );
	  
	  
	  $myspace_response = curl_exec($ch);
	  
	  $responseBody 	= '';
	  $responseHeader 	= '';
	  
	  list($responseHeader, $responseBody) = explode("\r\n\r\n", $myspace_response, 2);
	  
	  $responseStatus 		= (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	  $responseContentType 	= curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	  
	  //if a content type was found
	  if ($responseContentType) {
		  $responseContentType = preg_replace(
										  "/;.*/", 
										  "", 
										  $responseContentType
										  ); // strip off charset
	  }
	  
	  
	  $response = array(
		  			'contentType' => $responseContentType,
					'status' => $responseStatus,
					'headers' => $responseHeader,
					'body' => $responseBody,
					'raw' => $myspace_response
					);
	  
	  return $response;
  }
  
  
  
 

  /** Format and sign an OAuth / API request
   * @param 	$url
   * @param 	$args
   * @param 	$method
   *
   * */
  function oAuthRequest($url, $args=array(), $method=NULL) {
  /*
  @TODO: args do not make this a GET vs, POST, vs PUT, vs DELETE
  */
    if (empty($method)) $method = empty($args) ? "POST" : "GET";

    $req = OAuthRequest::from_consumer_and_token(
	    				$this->consumer,
	    				$this->token,
	    				$method,
	    				$url,
	    				$args);

    $req->sign_request($this->sha1_method, $this->consumer, $this->token);
    
    //for debuging later, writes the request to a file if configured
    if (self::$MS_DUMP_REQUESTS) {

      $k = $this->consumer->secret . "&";

      if ($this->token) $k .= $this->token->secret;

      date_default_timezone_set('UTC');
      $reqTime = date('c',time());
      
      $dump = ''; //clear dump
      $dump = "---\n\nOAUTH REQUEST TO $url";
      $dump .= "\n TIME:".$reqTime."  \n\n";
      
      if (!empty($args)){ $dump .= " WITH PARAMS: " . json_encode($args); }
      $dump .= 	"\n\nBase string: " . $req->base_string . 
      		"\nSignature string: $k\n";
		
      self::dump($dump);
      
      $dump =''; //clear dump

    }//end debug dump
    
    
    switch ($method) {
	    case 'GET':
	    	return $this->http($req->to_url());
	    	break;
	    case 'POST':
	    	return $this->http($req->get_normalized_http_url(),
				   $req->to_postdata());
	    	break;
    }
  }

  /**
   *  Make an HTTP request, throwing an exception if we get anything other than a 200 response
   * @param 	$url
   * @param 	$postData
   *
   * @return	$response
   * */
  public function http($url, $postData=null) {
    if (self::$MS_DUMP_REQUESTS) {

      self::dump("Final URL: $url\n\n");

      $url_bits = parse_url($url);

      if (isset($postData)) {

		self::dump(
			"POST ".$url_bits['path']." HTTP/1.0".
			"\nHost: ".$url_bits['host'].
			"\nContent-Type: application/x-www-form-urlencoded".
			"\nContent-Length: ".strlen($postData).
			"\n\n$postData\n"
			);

      }
      else {

		$get_url = $url_bits['path'];

		if ($url_bits['query']) $get_url .= '?' . $url_bits['query'];

		self::dump(
			"GET $get_url HTTP/1.0".
			"\nHost: ".$url_bits['host'].
			"\n\n"
			);

      }
    }// end if ms_dump_requests

    $ch = curl_init();

    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if (isset($postData)) {
	  //TRUE to do a regular HTTP POST. This POST is the normal application/x-www-form-urlencoded kind, most commonly used by HTML forms
      curl_setopt($ch, CURLOPT_POST, true);
	  //The full data to post in a HTTP "POST" operation. To post a file, prepend a filename with @ and use the full path. This can either be passed as a urlencoded string like 'para1=val1&para2=val2&...' or as an array with the field name as key and field data as value.
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }

    $data = curl_exec($ch);
    $response = '';
    list($header, $response) = explode("\r\n\r\n", $data, 2);
    
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ct = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    if ($ct) $ct = preg_replace("/;.*/", "", $ct); // strip off charset

    if (self::$MS_DUMP_REQUESTS) {
      self::dump("\n____RAW RESPONSE____\n$data\n____END RAW RESPONSE___\n\n");
    }
    
    if (!$status) throw new MySpaceException(
	    			"Connection to $url failed:<br/><br/>\r\n\r\n".
	      			"Response:<br/><br/>\r\n".
				$data, MySpaceException::CONNECT_FAILED);
/**
*@link http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
* not all responses are supported by the MySpace REST API
*
*@link http://developerwiki.myspace.com/index.php?title=MySpace_REST_Resources
*
*@link http://oauth.pbwiki.com/ProblemReporting
*
** 1xx **Informational
*  100 Continue
*  101 Switching Protocols
** 2xx **Success
*  200 status OK	**OK
*  201 created		**Created OK
*  202 accepted
*  203 non-authoritative information
*  204 No Content
*  205 reset content
*  206 partial content
*  207 multi-status
** 3xx **Redirection
*  300 multiple choices?  mime type?
*  301 moved peranetly
*  302 found
*  303 Redirect See Other
*  304 not modified (GET, HEAD, cache response)		**No Changes
*  305 use proxy (clients usually do not support this)
*  306 switch proxy (no longer used)
*  307 moved temporarily
** 4xx **Client Error
*  400 bad request
*  401 unauthorized		**unauthorized
*  402 payment required
*  403 forbidden
*  404 Not Found
*  405 Method Not Allowed (check GET, POST, PUT, DELETE, HEAD, TRACE)
*  406 not acceptable
*  407 proxy authenticatio required
*  408 request timeout
*  409 Conflict		**Conflict
*  410 Gone
*  411 lenth required
*  412 Precondition Failed
*  413 Request Entity Too Large
*  414 request URI too long
*  415 Unsupported media type
*  416 requested rante not satisfiable
*  417 exception failed
*  418 I'm a teapot
*  500 internal server error
*  501 not implmented			**internal server error
*  502 bad gateway
*  503 service unavailable		**server too busy
*  504 gateway timeout
*  505 http version not supported
*  506 variant also negotiates
*  507 insufficient storage
*  509 bandwidth limit exceeded
*  510 not extended
*/
    //things were not perfect, let's throw an error
    if ($status != 200) {
	//redirects should be valid REST responses!
	    
      switch($status){
	      
      	case 201:
		//i remember seeing this once before
		break;
      	case 401:
			/*  consumer key is not set!! or is wrong
	  Fatal error: Uncaught exception 'MySpaceException' with message 'Request to http://api.myspace.com/request_token?oauth_version=1.0&oauth_nonce=9a5139f46cb96314a7d5a3faf6ee95ca&oauth_timestamp=1238041272&oauth_consumer_key=NOT%20SET&oauth_signature_method=HMAC-SHA1&oauth_signature=8RqzekFfhFmAsXAql3RHJTX9A%2Fc%3D failed:<br/><br/> HTTP error 401 <br/><br/> Response:<br/><br/> HTTP/1.1 401 oauth_problem=consumer_key_unknown Date: Thu, 26 Mar 2009 04:21:12 GMT Server: Microsoft-IIS/6.0 WWW-Authenticate: OAuth realm="http://api.myspace.com/authorization", oauth_problem=consumer_key_unknown Set-Cookie: MSCulture=IP=208.113.234.71&IPCulture=en-US&PreferredCulture=en-US&PreferredCulturePending=&Country=VVM=&ForcedExpiration=633736128723757790&timeZone=0&myStuffDma=&USRLOC=QXJlYUNvZGU9NzE0JkNpdHk9QnJlYSZDb3VudHJ5Q29kZT1VUyZDb3VudHJ5TmFtZT1Vbml0ZWQgU3RhdGVzJkRtYUNvZGU9ODAzJkxhdGl0dWRlPTMzLjkyNjkmTG9uZ2l0dWRlPS0xMTcuODYxMiZQb3N0YWxDb2RlPTkyODIxJlJlZ2lvbk5hbWU9Q0E=; domain=.myspace.com; expires=Sat, 25-Apr-2009 04:21:12 GMT in /home/.jamshid/user1056/demos.jdavid.net/myspaceid-sdkv03252009/source/MySpaceID/myspace.php on line 1197
	  */
		//suspended app
		//insuffecient app permission
		//insuffecient user or application permission
		//incorrect user
		//missing or revoked token
		//insuffecient OpenCanvace Permissions
		//user has not added app
		//expired timestamp
		break;
      	case 403:
		//missing oauth params
		//expired timestamp
		//used nonce
		
		//invalid key
		
		//invalid token
		
		//invalid key
		/*
		It seems like the openid realm entered in the application page needs
		to have a trailing slash to work properly.  If that’s the case, we
		really need to either add it ourselves or warn the user when they
		enter it.  Also, when the realm doesn’t match, we return:
		
		403 "Neither the token nor the cookie is present to complete this call."
		*/
		break;
	case 404:
		//the resource is not found
		//missing user
		break;
      	case 500:
		//this is likely to be a realm mismatch error, but it could be something else
		break;
      }
      
      /**
      *  Response types
      *
      *  application/x-www-form-urlencoded
      *  application/json
      *  application/atom+xml
      */
      
      //ok, so its an unexpected error type, find the content type and throw a general error
      if ($ct == "application/json") {
		$r = json_decode($response);
		if ($r && isset($r->rsp) && $r->rsp->stat != 'ok') {

		  throw new MySpaceException($r->rsp->code.": ".$r->rsp->message."\r\n\r\n<br /><br /><pre>\r\n$header\r\n</pre>",
		  				MySpaceException::REMOTE_ERROR, $r->rsp);

		}
      }
      if ($ct == "application/atom+xml") {
	      	//we must have asked for an XML or ATOM doc type, but something went wrong
		$r = new SimpleXMLElement($response);

      }

      
      //throw a general error
      throw new MySpaceException(
	      			"Request to $url failed:<br/><br/>\r\n\r\n".
	      			"HTTP error $status <br/><br/>\r\n".
				"Response:<br/><br/>\r\n".
				$data,
      				MySpaceException::REQUEST_FAILED, $response);
    }
    curl_close ($ch);

    return $response;
  }
  
  /**
   * checks if token is present, else throws an exception
   *
   * */
  protected function requireToken() {
    if ($this->offsite && !isset($this->token)) {
      throw new MySpaceException(
      				"This function requires an OAuth token",
      				 MySpaceException::TOKEN_REQUIRED
      				 );
    }
  }
  
  /**
  *
  *
  */
  private function isSupportedMethod($method, $raiseException=false){
	  $value = false;
	  
	  //we will add support DELETE later
	  //HEAD, TRACE, etc... are NOT supported
	  $supported = array('GET','PUT','POST');
	  
	  $value = in_array($method, $supported, true);
	  
	  if($raiseException && $value == false){
		  //raise exceptions
	  }
	  
	  return $value;
  }
  
  /**
  *
  *
  */
  private function isSupportedRequestContentType($contentType, $raiseException=false){
	  $value = false;
	  $supported = array(
		  'application/x-www-form-urlencoded'
		  );
	  
	  $value = in_array( $contentType, $supported, true);
	  
	  //if $contentType = multi-part message throw different error
	  
	  if($raiseException && $value == false){
		  //raise exceptions
	  }
	  
	  return $value;
  }
  
  /**
  *
  *
  */
  private function isSupportedResponseContentType($contentType){
	  $value = false;
	  $supported = array(
		  'application/x-www-form-urlencoded',
		  'application/json',
		  'application/xml',
		  'application/atom+xml');
	  
	  $value = in_array($contentType, $supported, true);
	  
	  //if $contentType = multi-part message throw different error
	  
	  if($value == false){
		  //raise exceptions
	  }
	  
	  return $value;
  }
  

  /**
   * writes to an error log
   *
   * @param string $text
   */
  private function dump($text) {
    // if (!self::$MS_DUMP_REQUESTS) throw new Exception(
    	// 'MySpace::$MS_DUMP_REQUESTS must be set to enable request trace dumping');

    // file_put_contents(self::$MS_DUMP_REQUESTS, $text, FILE_APPEND);

  }

}
 