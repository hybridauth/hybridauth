<?php

/**
 * Draugiem.lv API library for PHP
 *
 * Version 1.2.4 (04-06-2012)

 *
 * Class for easier integration of your application with Draugiem.lv API.
 * Supports both draugiem.lv Passport applications and iframe based applications.
 * All user data information returned in functions is returned in following format:
 * 	array (
 * 		'uid' => 491171,
 * 		'name' => 'Jānis',
 * 		'surname' => 'Bērziņš',
 * 		'nick' => 'Jancis',
 * 		'place' => 'Rīga',
 * 		'age' => 26,
 * 		'adult' => true,
 * 		'img' => 'http://i2.ifrype.com/91/171/491171/sm_2008100616435822749.jpg',
 * 		'sex' => 'M',
 * 	)
 * If data of multiple users is returned, multiple user data items are placed in array
 * with user IDs as array keys.
 *
 *
 * Version history:
 * 1.2.4 (04-06-2012) - Added request handling and POST request option for apiCall method.
 * 1.2.3 (15-09-2010) - Added callback.html support for Javascript functions, changed JS_URL
 * 1.2.2 (21-07-2010) - Fixed http_build_query bug, removed short PHP open tags, fixed session cleanup
 * 1.2.1 (14-06-2010) - Added getOnlineFriends function
 * 1.2.0 (31-05-2010) - Many improvements for compatibility with iframe applications
 *
 * @copyright SIA Draugiem, 2012
 * @version 1.2.4
 */
class DraugiemApi {


	private $app_id; //application ID
	private $app_key;//application API key
	
	private $user_key = false; //user API key
	private $session_key = false; //user draugiem.lv session key
	private $userinfo = false; //active user
	private $lastTotal = array(); //last value cache of "total" attributes of friend list requests

	/**
	 * @var int Error code for last failed API request
	 */
	public $lastError = 0;
	/**
	 * @var int Error description for last failed API request
	 */
	public $lastErrorDescription = '';

	/**
	 * Draugiem.lv API URL
	 */
	const API_URL = 'http://api.draugiem.lv/php/';
	/**
	 * Draugiem.lv passport login URL
	 */
	const LOGIN_URL = 'http://api.draugiem.lv/authorize/';
	/**
	 * Iframe scripts URL
	 */
	const JS_URL = 'http://ifrype.com/applications/external/draugiem.js';

	/**
	 * Timeout in seconds for session_check requests
	 */
	const SESSION_CHECK_TIMEOUT = 180;
	
	/**
	 * Constructs Draugiem.lv API object
	 * 
	 * @param int $app_id your application ID
	 * @param string $app_key application API key
	 * @param string $user_key user API key (or empty if no user has been authorized)
	 */
	public function __construct($app_id, $app_key, $user_key = ''){
		$this->app_id = (int)$app_id;
		$this->app_key = $app_key;
		$this->user_key = $user_key;
	}

	/**
	 * Load draugiem.lv user data and validate session.
	 *
	 * If session is new, makes API authorize request and loads user data, otherwise
	 * gets user info stored in PHP session. Draugiem.lv session status is revalidated automatically
	 * by performing session_check requests in intervals specified by SESSION_CHECK_TIMEOUT constant.
	 *
	 * @return boolean Returns true on successful authorization or false on failure.
	 */
	public function getSession(){
		if(session_id()==''){//If no session exists, start new
			session_start();
		}
		if(isset($_GET['dr_auth_status']) && $_GET['dr_auth_status']!='ok'){
			$this->clearSession();
		} elseif(isset($_GET['dr_auth_code']) && (empty($_SESSION['draugiem_auth_code']) ||
			$_GET['dr_auth_code'] != $_SESSION['draugiem_auth_code'])){// New session authorization
				
			$this->clearSession(); //Delete current session data to prevent overwriting of existing session
			
			//Get authorization data
			$response = $this->apiCall('authorize', array('code'=>$_GET['dr_auth_code']));
			
			if($response && isset($response['apikey'])){//API key received
				//User profile info
				$userData = reset($response['users']);
				
				if(!empty($userData)){
					if(!empty($_GET['session_hash'])){//Internal application, store session key to recheck if draugiem.lv session is active 
						$_SESSION['draugiem_lastcheck'] = time();
						$this->session_key = $_SESSION['draugiem_session'] = $_GET['session_hash'];
						if(isset($_GET['domain'])){//Domain for JS actions
							$_SESSION['draugiem_domain'] = preg_replace('/[^a-z0-9\.]/','',$_GET['domain']);
						}
						if(!empty($response['inviter'])){//Fill invitation info if any
							$_SESSION['draugiem_invite'] = array(
								'inviter' => (int)$response['inviter'],
								'extra' => isset($response['invite_extra']) ? $response['invite_extra'] : false,
							);
						}
					}
					
					$_SESSION['draugiem_auth_code'] = $_GET['dr_auth_code'];

					//User API key
					$this->user_key = $_SESSION['draugiem_userkey'] = $response['apikey'];
					//User language
					$_SESSION['draugiem_language'] = $response['language'];
					//Profile info
					$this->userinfo = $_SESSION['draugiem_user'] = $userData;
					
					return true;//Authorization OK
				}
			}
			
		}elseif(isset($_SESSION['draugiem_user'])){//Existing session


			//Load data from session
			$this->user_key = $_SESSION['draugiem_userkey'];
			$this->userinfo = $_SESSION['draugiem_user'];

			if(isset($_SESSION['draugiem_lastcheck'], $_SESSION['draugiem_session'])){ //Iframe app session

				if(isset($_GET['dr_auth_code'], $_GET['domain'])){//Fix session domain if changed
					$_SESSION['draugiem_domain'] = preg_replace('/[^a-z0-9\.]/','',$_GET['domain']);
				}

				$this->session_key = $_SESSION['draugiem_session'];
				//Session check timeout not reached yet, do not check session
				if($_SESSION['draugiem_lastcheck'] > time() - self::SESSION_CHECK_TIMEOUT){
					return true;
				} else {//Session check timeout reached, recheck draugiem.lv session status
					$response = $this->apiCall('session_check', array('hash'=>$this->session_key));
					if(!empty($response['status']) && $response['status'] == 'OK'){
						$_SESSION['draugiem_lastcheck'] = time();
						return true;
					}
				}
			} else {
				return true;
			}
		}
		return false; //failure
	}
	
	/**
	 * Get user API key from current session. The function must be called after getSession().
	 *
	 * @return string API key of current user or false if no user has been authorized
	 */
	public function getUserKey(){
		return $this->user_key;
	}

	/**
	 * Get language setting of currently authorized user. The function must be called after getSession().
	 *
	 * @return string Two letter country code (lv/ru/en/de/hu/lt)
	 */
	public function getUserLanguage(){
		return isset($_SESSION['draugiem_language']) ? $_SESSION['draugiem_language'] : 'lv';
	}
	
	/**
	 * Get draugiem.lv user ID for currently authorized user
	 *
	 * @return int Draugiem.lv user ID of currently authorized user or false if no user has been authorized
	 */
	public function getUserId(){
		if($this->user_key && !$this->userinfo) { //We don't have user data, request
			$this->userinfo = $this->getUserData();
		}
		if(isset($this->userinfo['uid'])){//We have user data, return uid
			return $this->userinfo['uid'];
		} else {
			return false;
		}
	}
	

	/**
	 * Return user data for specified Draugiem.lv user IDs
	 *
	 * If a single user ID is passed  to this function, a single user data element is returned.
	 * If an array of user IDs is passed to this function, an array of user data elements is returned.
	 * Function can return only information about users that have authorized the application.
	 *
	 * @param mixed $ids array of user IDs or a single user ID (this argument can also be false. In that case, user data of current user will be returned)
	 * @return array Requested user data items or false if API request has failed
	 */
	public function getUserData ($ids = false){
		if(is_array($ids)){//Array of IDs
			$ids = implode(',', $ids);
		} else {//Single ID
			$return_single = true;
			
			if($this->userinfo && ($ids == $this->userinfo['uid'] || $ids === false)){//If we have userinfo of active user, return it immediately
				return $this->userinfo;
			}

			if($ids!==false){
				$ids = (int)$ids;
			}
		}
		
		$response = $this->apiCall('userdata', array('ids'=>$ids));
		if($response){
			$userData = $response['users'];
			if(!empty($return_single)){//Single item requested
				if(!empty($userData)){//Data received
					return reset($userData);
				} else {//Data not received
					return false;
				}
			} else {//Multiple items requested
				return $userData;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get user profile image URL with different size
	 * @param string $img User profile image URL from API (default size)
	 * @param string $size Desired image size (icon/small/medium/large)
	 */
	public function imageForSize($img, $size){
		$sizes = array(
			'icon' => 'i_', //50x50px
			'small'=>'sm_', //100x100px (default)
			'medium' => 'm_', //215px wide
			'large' => 'l_', //710px wide
		);
		if(isset($sizes[$size])){
			$img = str_replace('/sm_', '/'.$sizes[$size], $img);
		}
		return $img;
	}

	
	/**
	 * Check if two application users are friends
	 *
	 * @param int $uid User ID of the first user
	 * @param int $uid2 User ID of the second user (or false to use current user)
	 * @return boolean Returns true if the users are friends, false otherwise
	 */
	public function checkFriendship($uid, $uid2 = false){
		$response = $this->apiCall('check_friendship', array('uid'=>$uid, 'uid2'=>$uid2));
		if(isset($response['status']) && $response['status'] == 'OK'){
			return true;
		}
		return false;
	}

	/**
	 * Get number of user friends within application
	 *
	 * To reach better performance, it is recommended to call this function after getUserFriends() call
	 * (in that way, a single API request will be made for both calls).
	 *
	 * @return integer Returns number of friends or false on failure
	 */
	public function getFriendCount(){
		if(isset($this->lastTotal['friends'][$this->user_key])){
			return $this->lastTotal['friends'][$this->user_key];
		}
		$response = $this->apiCall('app_friends_count');
		if(isset($response['friendcount'])){
			$this->lastTotal['friends'][$this->user_key] = (int)$response['friendcount'];
			return $this->lastTotal['friends'][$this->user_key];
		}
		return false;
	}

	/**
	 * Get list of friends of currently authorized user that also use this application.
	 *
	 * @param integer $page Which page of data to return (pagination starts with 1, default value 1)
	 * @param integer $limit Number of users per page (min value 1, max value 200, default value 20)
	 * @param boolean $return_ids Whether to return only user IDs or full profile information (true - IDs, false - full data)
	 * @return array List of user data items/user IDs or false on failure
	 */
	public function getUserFriends($page = 1, $limit = 20, $return_ids = false){
		$response = $this->apiCall('app_friends', array( 'show'=>($return_ids?'ids':false), 'page'=>$page, 'limit'=>$limit ));
		if($response){
			$this->lastTotal['friends'][$this->user_key] = (int)$response['total'];
			if($return_ids){
				return $response['userids'];
			} else {
				return $response['users'];
			}
		} else {
			return false;
		}			
	}
	
	/**
	 * Get list of all friends of currently authorized user.
	 *
	 * @param integer $page Which page of data to return (pagination starts with 1, default value 1)
	 * @param integer $limit Number of users per page (min value 1, max value 200, default value 20)
	 * @param boolean $return_ids Whether to return only user IDs or full profile information (true - IDs, false - full data)
	 * @return array List of user data items/user IDs or false on failure
	 */
	public function getAllUserFriends($page = 1, $limit = 20, $return_ids = false){
		$response = $this->apiCall('app_all_friends', array( 'show'=>($return_ids?'ids':false), 'page'=>$page, 'limit'=>$limit ));
		if($response){
			$this->lastTotal['friends'][$this->user_key] = (int)$response['total'];
			if($return_ids){
				return $response['userids'];
			} else {
				return $response['users'];
			}
		} else {
			return false;
		}			
	}	

	/**
	 * Get list of friends of currently authorized user that also use this application and are currently logged in draugiem.lv.
	 * Function available only to integrated applications.
	 *
	 * @param integer $limit Number of users per page (min value 1, max value 100, default value 20)
	 * @param boolean $in_app Whether to return friends that currently use app (true - online in app, false - online in portal)
	 * @param boolean $return_ids Whether to return only user IDs or full profile information (true - IDs, false - full data)
	 * @return array List of user data items/user IDs or false on failure
	 */
	public function getOnlineFriends($limit = 20, $in_app=false, $return_ids = false){
		$response = $this->apiCall('app_friends_online', array( 'show'=>($return_ids?'ids':false), 'in_app'=>$in_app, 'limit'=>$limit ));
		if($response){
			if($return_ids){
				return $response['userids'];
			} else {
				return $response['users'];
			}
		} else {
			return false;
		}
	}

	/**
	 * Get number of users that have authorized the application
	 *
	 * To reach better performance, it is recommended to call this function after getAppUsers() call
	 * (in that way, a single API request will be made for both calls).
	 *
	 * @return integer Returns number of users or false on failure
	 */
	public function getUserCount(){
		if(isset($this->lastTotal['users'])){
			return $this->lastTotal['users'];
		}
		$response = $this->apiCall('app_users_count');
		if(isset($response['usercount'])){
			$this->lastTotal['users'] = (int)$response['usercount'];
			return $this->lastTotal['users'];
		}
		return false;
	}

	/**
	 * Get list of users that have authorized this application.
	 *
	 * @param integer $page Which page of data to return (pagination starts with 1, default value 1)
	 * @param integer $limit Number of users per page (min value 1, max value 200, default value 20)
	 * @param boolean $return_ids Whether to return only user IDs or full profile information (true - IDs, false - full data)
	 * @return array List of user data items/user IDs or false on failure
	 */
	public function getAppUsers($page = 1, $limit = 20, $return_ids = false){
		$response = $this->apiCall('app_users', array( 'show'=>($return_ids?'ids':false), 'page'=>$page, 'limit'=>$limit ));
		if($response){
			$this->lastTotal['users'] = (int)$response['total'];
			if($return_ids){
				return $response['userids'];
			} else {
				return $response['users'];
			}
		} else {
			return false;
		}			
	}



	################################################
	###### Draugiem.lv passport functions ##########
	################################################
	
	/**
	 * Get URL for Draugiem.lv Passport login page to authenticate user
	 *
	 * @param string $redirect_url URL where user has to be redirected after authorization. The URL has to be in the same domain as URL that has been set in the properties of the application.
	 * @return string URL of Draugiem.lv Passport login page
	 */
	public function getLoginURL ($redirect_url){
		$hash = md5($this->app_key.$redirect_url);//Request checksum
		$link = self::LOGIN_URL.'?app='.$this->app_id.'&hash='.$hash.'&redirect='.urlencode($redirect_url);
		return $link;
	}
	
	/**
	 * Get HTML for Draugiem.lv Passport login button with Draugiem.lv Passport logo.
	 *
	 * @param string $redirect_url URL where user has to be redirected after authorization. The URL has to be in the same domain as URL that has been set in the properties of the application.
	 * @param boolean $popup Whether to open authorization page within a popup window (true - popup, false - same window).
	 * @return string HTML of Draugiem.lv Passport login button
	 */
	public function getLoginButton($redirect_url, $popup = true){
		$url = htmlspecialchars($this->getLoginUrl($redirect_url));
		
		if($popup){
		$js = "if(handle=window.open('$url&amp;popup=1','Dr_{$this->app_id}' ,'width=400, height=400, left='+(screen.width?(screen.width-400)/2:0)+', top='+(screen.height?(screen.height-400)/2:0)+',scrollbars=no')){handle.focus();return false;}";
			$onclick = ' onclick="'.$js.'"';
		} else {
			$onclick = '';
		}
		return '<a href="'.$url.'"'.$onclick.'><img border="0" src="http://api.draugiem.lv/authorize/login_button.png" alt="draugiem.lv" /></a>';
	}

	############################################
	###### Iframe application functions ####
	############################################

	/**
	 * Get draugiem.lv domain (usually "www.draugiem.lv" but can be different for international versions of the portal) for current iframe session.
	 *
	 * This function has to be called after getSession()). Domain name should be used when linking to user profiles or other targets within draugiem.lv.
	 *
	 * @return string Draugiem.lv domain name that is currently used by application user
	 */
	public function getSessionDomain(){
		return isset($_SESSION['draugiem_domain']) ? $_SESSION['draugiem_domain'] : 'www.draugiem.lv';
	}

	/**
	 * Get information about accepted invite from current session.
	 *
	 * If user has just joined the application after accepting an invitation, function returns array
	 * with two elements:
	 * inviter - User ID of the person who sent invitation.
	 * extra - Extra string data attached to invitation (or false if there are no data)
	 * This function has to be called after getSession().
	 *
	 * @return returns array with invitation info or false if no invitation has been accepted.
	 */
	public function getInviteInfo(){
		return isset($_SESSION['draugiem_invite']) ? $_SESSION['draugiem_invite'] : false;
	}

	 /**
	  * Get HTML for embedding Javascript code to allow to resize application iframe and perform other actions.
	  *
	  * Javascript code will automatically try to resize iframe according to the height of DOM element with ID that is passed
	  * in $resize_container parameter.
	  *
	  * Function also enables Javascript callback values if $callback_html argument is passed. It has to contain full
	  * address of the copy of callback.html on the application server (e.g. http://example.com/callback.html).
	  * Original can be found at http://www.draugiem.lv/applications/external/callback.html
	  *
	  * This function has to be called after getSession().
	  *
	  * @param string $resize_container DOM element ID of page container element
	  * @param string $callback_html address of callback.html Optional if no return values for Javascript API functions are needed.
	  * @return string HTML code that needs to be displayed to embed Draugiem.lv Javascript
	  */
	public function getJavascript($resize_container = false, $callback_html = false){
		$data = '<script type="text/javascript" src="'.self::JS_URL.'" charset="utf-8"></script>'."\n";
		$data.= '<script type="text/javascript">'."\n";
		if($resize_container){
			$data.= " var draugiem_container='$resize_container';\n";
		}
		if(!empty($_SESSION['draugiem_domain'])){
			$data.= " var draugiem_domain='".$this->getSessionDomain()."';\n";
		}
		if($callback_html){
			$data.= " var draugiem_callback_url='".$callback_html."';\n";
		}
		$data.='</script>'."\n";
		return $data;
	}

	/**
	 * Workaround for cookie creation problems in iframe with IE and Safari.
	 *
	 * This function has to be called before getSession() and after session_start()
	 */
	public function cookieFix(){

		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		//Set up P3P policy to allow cookies in iframe with IE
		if(strpos($user_agent, 'MSIE')){
			header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
		}

		//Workaround for Safari - post a form with Javascript to create session cookie
		if(empty($_COOKIE[session_name()]) && strpos($user_agent,'Safari') && isset($_GET['dr_auth_code']) && !isset($_GET['dr_cookie_fix'])){
	?>
		<html><head><title>Iframe Cookie fix</title></head>
		<body>
			<form name="cookieFix" method="get" action="">
				<?php foreach($_GET as $key=>$val){
					echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'" />';
				} ?>
				<input type="hidden" name="dr_cookie_fix" value="1" />
				<noscript><input type="submit" value="Continue" /></noscript>
			</form>
			<script type="text/javascript">document.cookieFix.submit();</script>
		</body></html>
	<?php
			exit;
		}

	}


	##########################################################
	### Functions available only for approved applications ###
	##########################################################

	/**
	 * Adds entry to current user's profile activity feed. Function available only for selected applications.
	 * Contact api@draugiem.lv to request posting permissions for your application.
	 * @param string $text Link text of the activity
	 * @param string $prefix Text before the activity link
	 * @param string $link Target URL of the activity link (must be in the same domain as application).
	 * @param boolean $page_id Business page id.
	 * @return boolean Returns true if activity was created successfully or false if it was not created (permission was denied or activity posting limit was reached).
	 */
	public function addActivity($text, $prefix=false, $link = false, $page_id = false){
		$response = $this->apiCall('add_activity', array('text'=>$text,'prefix'=>$prefix, 'link'=>$link, 'page_id' => $page_id));
		if(!empty($response['status'])){
			if($response['status'] =='OK'){
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds notification to current user's profile news box. Function available only for selected applications.
	 * Contact api@draugiem.lv to request posting permissions for your application.
	 * @param string $text Link text of the notification
	 * @param string $prefix Text before the notification link
	 * @param string $link Target URL of the notification link (must be in the same domain as application).
	 * @param int $creator User ID of the user that created the notification (if it is 0, application name wil be shown as creator)
	 * @return boolean Returns true if notification was created successfully or false if it was not created (permission was denied or posting limit was reached).
	 */
	public function addNotification($text, $prefix=false, $link = false, $creator=0){
		$response = $this->apiCall('add_notification', array('text'=>$text,'prefix'=>$prefix, 'link'=>$link, 'creator'=>$creator));
		if(!empty($response['status'])){
			if($response['status'] =='OK'){
				return true;
			}
		}
		return false;
	}

	#########################
	### Utility functions ###
	#########################
		
	/**
	 * Inner function that calls Draugiem.lv API and returns response as an array
	 *
	 * @param string $action API action that has to be called
	 * @param array $args Key/value pairs of additional parameters for the request (excluding app, apikey and action)
	 * @return mixed API response data or false if the request has failed
	 */
	public function apiCall($action, $args = array(), $method = 'GET'){
		
		if(($method == 'POST') && function_exists('curl_init')){
			$params = array();
			$params['app'] = $this->app_key;
			if($this->user_key){//User has been authorized
				$params['apikey'] = $this->user_key;
			}
			$params['action'] = $action;
			$params += $args;
			
			$ch = curl_init(self::API_URL);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			$response = curl_exec($ch);
			curl_close($ch);
			return $response;
		}
		
		$url =self::API_URL.'?app='.$this->app_key;
		if($this->user_key){//User has been authorized
			$url.='&apikey='.$this->user_key;
		}
		$url.='&action='.$action;
		if(!empty($args)){
			foreach($args as $k=>$v){
				if($v!==false){
					$url.='&'.urlencode($k).'='.urlencode($v);
				}
			}
		}
		if(ini_get('allow_url_fopen') == 1){
			$response = @file_get_contents($url);//Get API response (@ to avoid accidentaly displaying API keys in case of errors)
		}elseif(function_exists('curl_init')){
			$ch = curl_init();
			$timeout = 5;
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
			$response = curl_exec($ch);
			curl_close($ch);			
		}else{
            $parts = parse_url($url); 
            $target = $parts['host'];
            $port = 80;
            
            $page    = isset($parts['path'])        ? $parts['path']            : '';
            $page   .= isset($parts['query'])       ? '?' . $parts['query']     : '';
            $page   .= isset($parts['fragment'])    ? '#' . $parts['fragment']  : '';
            $page    = ($page == '')                ? '/'                       : $page;
            if($fp = fsockopen($target, $port, $errno, $errstr, 30))
            {
                $headers  = "GET $page HTTP/1.1\r\n";
                $headers .= "Host: {$parts['host']}\r\n";
                $headers .= "Connection: Close\r\n\r\n";
                if(fwrite($fp, $headers)){
                    $response = '';    
					while (!feof($fp)) {
						$response .= fgets($fp, 128);
					}		
					$response = substr($response, stripos($response, 'a:'));	
                }
                fclose($fp);
            }
		}

		if($response === false){//Request failed
			$this->lastError = 1;
			$this->lastErrorDescription = 'No response from API server';
			return false;
		}
		
		$response = unserialize($response);

		if(empty($response)){//Empty response
			$this->lastError = 2;
			$this->lastErrorDescription = 'Empty API response';
			return false;
		} else {
			if(isset($response['error'])){//API error, fill error attributes
				$this->lastError = $response['error']['code'];
				$this->lastErrorDescription = 'API error: '.$response['error']['description'];
				return false;						
			} else {
				return $response;
			}
		}
	}

	private function clearSession(){
			unset(
				$_SESSION['draugiem_auth_code'],
				$_SESSION['draugiem_session'],
				$_SESSION['draugiem_userkey'],
				$_SESSION['draugiem_user'],
				$_SESSION['draugiem_lastcheck'],
				$_SESSION['draugiem_language'],
				$_SESSION['draugiem_domain'],
				$_SESSION['draugiem_invite']
			);
	}
}

class Draugiem_Api extends DraugiemApi {}