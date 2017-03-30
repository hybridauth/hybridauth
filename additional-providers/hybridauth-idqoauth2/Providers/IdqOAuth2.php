<?php

/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 * idQ OAuth 2 Authentication
 * (c) 2017 inBay Technologies Inc.
 */

/**
 * To implement an OAuth 2 based service provider, Hybrid_Provider_Model_OAuth2
 * can be used to save the hassle of the authentication flow.
 *
 * Each class that inherit from Hybrid_Provider_Model_IdqOAuth2 have to implement
 * at least 2 methods:
 *   Hybrid_Providers_{provider_name}::initialize()     to setup the provider api end-points urls
 *   Hybrid_Providers_{provider_name}::getUserProfile() to grab the user profile
 *
 * Hybrid_Providers_IdqOAuth2 uses IdqOAuth2Client which can be found on
 * hybridauth-idqoauth2/thirdparty/IdqOAuth2/IdqOAuth2/IdqOAuth2Client.php
 */
class Hybrid_Providers_IdqOAuth2 extends Hybrid_Provider_Model {

	/**
	 * Default permissions
	 * @var string
	 */
	public $scope = "user_profile";

	/**
	 * Provider API wrapper
	 * @var OAuth2Client
	 */
	public $api = null;

	// The 'state' variable helps to prevent CSRF attacks,
	// and can also be used to identify the authentication request.
	protected $state = NULL;

	/**
	 * Try to get the error message from provider api
	 *
	 * @param int $code Error code
	 * @return string
	 */
	function errorMessageByStatus($code = null) {
		$http_status_codes = array(
			200 => "OK: Success!",
			304 => "Not Modified: There was no new data to return.",
			400 => "Bad Request: The request was invalid.",
			401 => "Unauthorized.",
			403 => "Forbidden: The request is understood, but it has been refused.",
			404 => "Not Found: The URI requested is invalid or the resource requested does not exists.",
			406 => "Not Acceptable.",
			500 => "Internal Server Error: Something is broken.",
			502 => "Bad Gateway.",
			503 => "Service Unavailable."
		);

		if (!$code && $this->api) {
			$code = $this->api->http_code;
		}

		if (isset($http_status_codes[$code])) {
			return $code . " " . $http_status_codes[$code];
		}
	}

	/**
	 * Adapter initializer
	 */
	function initialize() {
		if (!$this->config['keys']['id'] || !$this->config['keys']['secret']) {
			throw new UnexpectedValueException("Your application id and secret are required in order to connect to {$this->providerId}.", 4);
		}

		// include idQ OAuth2 client
		require_once Hybrid_Auth::$config['path_libraries'] . "IdqOAuth2/IdqOAuth2Client.php";

		// create a new idQ OAuth2 client instance
		$this->api = new IdqOAuth2Client($this->config['keys']['id'], $this->config['keys']['secret'], $this->endpoint);

		// If we have an access token, set it
		if ($this->token('access_token')) {
			$this->api->access_token = $this->token('access_token');
			$this->api->refresh_token = $this->token('refresh_token');
			$this->api->access_token_expires_in = $this->token('expires_in');
			$this->api->access_token_expires_at = $this->token('expires_at');
		}

		// Set curl proxy if exist
		if (isset(Hybrid_Auth::$config['proxy'])) {
			$this->api->curl_proxy = Hybrid_Auth::$config['proxy'];
		}

		$base_url = $this->config['oauth2_server'];
		$this->api->api_base_url   = $base_url;
		$this->api->authorize_url  = $base_url . '/api/v1/auth';
		$this->api->token_url      = $base_url . '/api/v1/token';

		if (isset($this->config['redirect_uri'])) {
			$this->api->redirect_uri = $this->config['redirect_uri'];
		}
		// override requested scope
		if (isset($this->config['scope']) && !empty($this->config['scope'])) {
			$this->scope = $this->config['scope'];
		}

		if (isset($this->config['state'])) {
			$this->state = $this->config['state'];
		}

		if ($this->config['skip_ssl']) {
			$this->api->curl_ssl_verifypeer = FALSE;
			$this->api->curl_ssl_verifyhost = FALSE;
		}
		if (isset($this->config['http_proxy']) && $this->config['http_proxy']) {
			$this->api->curl_proxy = $this->config['http_proxy'];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	function loginBegin() {

		if (!isset($this->state)) {
			$this->state = md5(uniqid(rand(), TRUE));
		}
		$this->saveState($this->state);
		$extra_params['state'] = $this->state;

		if (isset($this->scope)) {
			$extra_params['scope'] = $this->scope;
		}

		// redirect the user to the provider authentication url
		Hybrid_Auth::redirect($this->api->authorizeUrl($extra_params));
	}

	/**
	 * {@inheritdoc}
	 */
	function loginFinish() {
	    // check that the CSRF state token is the same as the one provided
		$this->checkState();

		$error = (array_key_exists('error', $_REQUEST)) ? $_REQUEST['error'] : "";

		// check for errors
		if ($error) {
			throw new UnexpectedValueException("Authentication failed! {$this->providerId} returned an error: $error", 5);
		}

		// try to authenticate user
		$code = (array_key_exists('code', $_REQUEST)) ? $_REQUEST['code'] : "";

		try {
			$this->api->authenticate($code);
		} catch (Exception $e) {
			throw new UnexpectedValueException("User profile request failed! {$this->providerId} returned an error: $e", 6);
		}

		// check if authenticated
		if (!$this->api->access_token) {
			throw new UnexpectedValueException("Authentication failed! {$this->providerId} returned an invalid access token.", 5);
		}

		// store tokens
		$this->token("access_token", $this->api->access_token);
		$this->token("refresh_token", $this->api->refresh_token);
		$this->token("expires_in", $this->api->access_token_expires_in);
		$this->token("expires_at", $this->api->access_token_expires_at);

		// set user connected locally
		$this->setUserConnected();
	}

	/**
	 * {@inheritdoc}
	 */
	function refreshToken() {
		// have an access token?
		if ($this->api->access_token) {

			// have to refresh?
			if ($this->api->refresh_token && $this->api->access_token_expires_at) {

				// expired?
				if ($this->api->access_token_expires_at <= time()) {
					$response = $this->api->refreshToken(array("refresh_token" => $this->api->refresh_token));

					if (!isset($response->access_token) || !$response->access_token) {
						// set the user as disconnected at this point and throw an exception
						$this->setUserUnconnected();

						throw new UnexpectedValueException("The Authorization Service has return an invalid response while requesting a new access token. " . (string) $response->error);
					}

					// set new access_token
					$this->api->access_token = $response->access_token;

					if (isset($response->refresh_token)) {
						$this->api->refresh_token = $response->refresh_token;
					}

					if (isset($response->expires_in)) {
						$this->api->access_token_expires_in = $response->expires_in;

						// even given by some idp, we should calculate this
						$this->api->access_token_expires_at = time() + $response->expires_in;
					}
				}
			}

			// re store tokens
			$this->token("access_token", $this->api->access_token);
			$this->token("refresh_token", $this->api->refresh_token);
			$this->token("expires_in", $this->api->access_token_expires_in);
			$this->token("expires_at", $this->api->access_token_expires_at);
		}
	}

	/**
	* Save the given $state in session.
	*/
	protected function saveState($state) {
		$session_var_name = 'state_' . $this->api->client_id;
		$_SESSION['HybridAuth']['IdqOAuth2'][$session_var_name] = $state;
	}

	/**
	* Read the state from session.
	*/
	protected function readState() {
		$session_var_name = 'state_' . $this->api->client_id;
		$state = ( isset($_SESSION['HybridAuth']['IdqOAuth2'][$session_var_name])
			? $_SESSION['HybridAuth']['IdqOAuth2'][$session_var_name]
			: NULL );
		unset($_SESSION['HybridAuth']['IdqOAuth2'][$session_var_name]);
		return $state;
	}

	/**
	* Check the state in the request against the one saved in session.
	*/
	protected function checkState() {
		$state = $this->readState();
		if (!$state || !isset($_REQUEST['state']) || $state != $_REQUEST['state']) {
			throw new UnexpectedValueException('Authentication failed! CSRF state token does not match the one provided.');
		}
	}

	/**
	* set propper headers before posting
	*/
	function get($url) {
		$this->api->curl_header =
		array(
			'Accept: application/json',
	    );		
		return $this->api->get($url);
	}

	/**
	* Load the user profile from the api client.
	*/
	function getUserProfile() {
		// Refresh tokens if needed.
		$this->refreshToken();

		// Get user profile.
		$response = $this->get('/api/v1/user');
		if (!isset($response->username)) {
			throw new UnexpectedValueException( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		// Set and return the hybridauth profile.		
		$this->user->profile->identifier = $response->username;
		$this->user->profile->displayName = (property_exists($response, 'email')) ? $response->email : $response->username;
		$this->user->profile->username = (property_exists($response, 'email')) ? $response->email : $response->username;
		$this->user->profile->email = (property_exists($response, 'email')) ? $response->email : ((property_exists($verified, 'email')) ? $verified->email : "");
		
		return $this->user->profile;
	}

}

