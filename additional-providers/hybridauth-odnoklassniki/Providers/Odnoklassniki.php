<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Odnoklassniki provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Odnoklassniki extends Hybrid_Provider_Model_OAuth2
{
	/**
	* ID wrappers initializer
	*/
	function initialize()
	{
		parent::initialize();
		// Provider apis end-points
		$this->api->api_base_url    = "http://api.ok.ru/fb.do";
		$this->api->authorize_url   = "http://connect.ok.ru/oauth/authorize";
		$this->api->token_url       = "http://api.odnoklassniki.ru/oauth/token.do";
		$this->api->sign_token_name = "access_token";
	}

	private function request($url, $params=false, $type="GET")
	{
		Hybrid_Logger::info("Enter OAuth2Client::request($url)");
		Hybrid_Logger::debug("OAuth2Client::request(). dump request params: ", serialize($params));
		if ($type === "GET"){
			$url = $url . (strpos($url, '?') ? '&' : '?') . http_build_query($params, '', '&');
		}
		$this->http_info = array();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL            , $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1);
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->api->curl_time_out);
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->api->curl_useragent);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->api->curl_connect_time_out);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->api->curl_ssl_verifypeer);
		curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->api->curl_header);
		if ($this->api->curl_proxy) {
			curl_setopt($ch, CURLOPT_PROXY  , $this->api->curl_proxy);
		}
		if ($type === "POST") {
			curl_setopt($ch, CURLOPT_POST, 1);
			if ($params) curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
		$response = curl_exec($ch);
		Hybrid_Logger::debug("OAuth2Client::request(). dump request info: ", serialize(curl_getinfo($ch)));
		Hybrid_Logger::debug("OAuth2Client::request(). dump request result: ", serialize($response));
		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ch));
		curl_close ($ch);
		return $response;
	}

	private function parseRequestResult($result)
	{
		if (json_decode($result)) return json_decode($result);
		parse_str($result, $output);
		$result = new StdClass();
		foreach ($output as $k => $v) {
			$result->$k = $v;
		}
		return $result;
	}

	function authodnoklass($code)
	{
		$params = array(
			"client_id"     => $this->api->client_id,
			"client_secret" => $this->api->client_secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->api->redirect_uri,
			"code"          => $code
		);

		$response = $this->request($this->api->token_url, http_build_query($params, '', '&'), $this->api->curl_authenticate_method);
		$response = $this->parseRequestResult($response);

		if (!$response || !isset($response->access_token)) {
			throw new Exception("The Authorization Service has return: " . $response->error);
		}
		if (isset($response->access_token)) $this->api->access_token          = $response->access_token;
		if (isset($response->refresh_token)) $this->api->refresh_token        = $response->refresh_token;
		if (isset($response->expires_in)) $this->api->access_token_expires_in = $response->expires_in;

		// Calculate when the access token expire.
		// At this moment Odnoklassniki does not return expire time in response.
		// 30 minutes expire time staten in dev docs http://apiok.ru/wiki/pages/viewpage.action?pageId=42476652
		if (isset($response->expires_in)) {
			$this->api->access_token_expires_at = time() + $response->expires_in;
		}
		else {
			$this->api->access_token_expires_at = time() + 1800;
		}
		return $response;
	}

	function loginFinish()
	{
		$error = (array_key_exists('error',$_REQUEST))?$_REQUEST['error']:"";
		// Check for errors
		if ($error) {
			throw new Exception("Authentication failed! {$this->providerId} returned an error: $error", 5);
		}
		// Try to authenticate user
		$code = (array_key_exists('code',$_REQUEST))?$_REQUEST['code']:"";
		try {
			$this->authodnoklass($code);
		}
		catch (Exception $e) {
			throw new Exception("User profile request failed! {$this->providerId} returned an error: $e->getMessage() ", 6);
		}
		// Check if authenticated
		if (!$this->api->access_token) {
			throw new Exception("Authentication failed! {$this->providerId} returned an invalid access token.", 5);
		}
		// Store tokens
		$this->token("access_token" , $this->api->access_token);
		$this->token("refresh_token", $this->api->refresh_token);
		$this->token("expires_in"   , $this->api->access_token_expires_in);
		$this->token("expires_at"   , $this->api->access_token_expires_at);
		// Set user connected locally
		$this->setUserConnected();
	}

	/**
	* Load the user profile.
	*/
	function getUserProfile()
	{
		// Set fields you want to get from OK user profile.
		// @see https://apiok.ru/wiki/display/api/users.getCurrentUser+ru
		// @see https://apiok.ru/wiki/display/api/fields+ru
		$fields = "UID,LOCALE,FIRST_NAME,LAST_NAME,NAME,GENDER,AGE,BIRTHDAY,HAS_EMAIL,EMAIL,CURRENT_STATUS,CURRENT_STATUS_ID,CURRENT_STATUS_DATE,ONLINE,PHOTO_ID,PIC190X190,PIC640X480,LOCATION";

		// Signature
		$sig = md5('application_key=' . $this->config['keys']['key'] . 'fields=' . $fields .'method=users.getCurrentUser' . md5($this->api->access_token . $this->api->client_secret));
		// Signed request
		$response = $this->api->api('?application_key=' . $this->config['keys']['key'] . '&fields=' . $fields .'&method=users.getCurrentUser&sig=' . $sig);

		if (!isset($response->uid)) {
			throw new Exception("User profile request failed! {$this->providerId} returned an invalid response.", 6);
		}

		$this->user->profile->identifier    = (property_exists($response,'uid'))?$response->uid:"";
		$this->user->profile->firstName     = (property_exists($response,'first_name'))?$response->first_name:"";
		$this->user->profile->lastName      = (property_exists($response,'last_name'))?$response->last_name:"";
		$this->user->profile->displayName   = (property_exists($response,'name'))?$response->name:"";
		// Get better size of user avatar
		$this->user->profile->photoURL      = (property_exists($response,'pic190x190'))?$response->pic190x190:"";
		$this->user->profile->photoBIG      = (property_exists($response,'pic640x480'))?$response->pic640x480:"";
		$this->user->profile->profileURL    = (property_exists($response,'link'))?$response->link:"";
		$this->user->profile->gender        = (property_exists($response,'gender'))?$response->gender:"";
		$this->user->profile->email         = (property_exists($response,'email'))?$response->email:"";
		$this->user->profile->emailVerified = (property_exists($response,'email'))?$response->email:"";
		if (property_exists($response, 'birthday')) {
			list($birthday_year, $birthday_month, $birthday_day) = explode('-', $response->birthday);
			$this->user->profile->birthDay   = (int) $birthday_day;
			$this->user->profile->birthMonth = (int) $birthday_month;
			$this->user->profile->birthYear  = (int) $birthday_year;
		}
		return $this->user->profile;
	}
}
