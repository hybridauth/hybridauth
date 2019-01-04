<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Providers_Google provider adapter based on OAuth2 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
 */
class Hybrid_Providers_Google extends Hybrid_Provider_Model_OAuth2 {

	/**
	 * > more infos on google APIs: http://developer.google.com (official site)
	 * or here: http://discovery-check.appspot.com/ (unofficial but up to date)
	 * default permissions
	 * {@inheritdoc}
	 */
	public $scope = "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.google.com/m8/feeds/";

	/**
	 * {@inheritdoc}
	 */
	function initialize() {
		parent::initialize();

		// Provider api end-points
		$this->api->authorize_url = "https://accounts.google.com/o/oauth2/auth";
		$this->api->token_url = "https://accounts.google.com/o/oauth2/token";
		$this->api->token_info_url = "https://www.googleapis.com/oauth2/v2/tokeninfo";

		// Google POST methods require an access_token in the header
		$this->api->curl_header = array("Authorization: OAuth " . $this->api->access_token);

		// Override the redirect uri when it's set in the config parameters. This way we prevent
		// redirect uri mismatches when authenticating with Google.
		if (isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])) {
			$this->api->redirect_uri = $this->config['redirect_uri'];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	function loginBegin() {
		$parameters = array("scope" => $this->scope, "access_type" => "offline");
		$optionals = array("scope", "access_type", "redirect_uri", "approval_prompt", "hd", "state");

		foreach ($optionals as $parameter) {
			if (isset($this->config[$parameter]) && !empty($this->config[$parameter])) {
				$parameters[$parameter] = $this->config[$parameter];
			}
			if (isset($this->config["scope"]) && !empty($this->config["scope"])) {
				$this->scope = $this->config["scope"];
			}
		}

		if (isset($this->config['force']) && $this->config['force'] === true) {
			$parameters['approval_prompt'] = 'force';
		}

		Hybrid_Auth::redirect($this->api->authorizeUrl($parameters));
	}

	/**
	 * {@inheritdoc}
	 */
	function getUserProfile() {
		// refresh tokens if needed
		$this->refreshToken();

		$response = $this->api->api("https://www.googleapis.com/oauth2/v3/userinfo");
		if (!isset($response->sub) || isset($response->error)) {
			throw new Exception("User profile request failed! {$this->providerId} returned an invalid response:" . Hybrid_Logger::dumpData( $response ), 6);
		}

		$this->user->profile->identifier = (property_exists($response, 'sub')) ? $response->sub : "";
		$this->user->profile->firstName = (property_exists($response, 'given_name')) ? $response->given_name : "";
		$this->user->profile->lastName = (property_exists($response, 'family_name')) ? $response->family_name : "";
		$this->user->profile->displayName = (property_exists($response, 'name')) ? $response->name : "";
		$this->user->profile->photoURL = (property_exists($response, 'picture')) ? $response->picture : "";
		$this->user->profile->profileURL = (property_exists($response, 'profile')) ? $response->profile : "";
		$this->user->profile->gender = (property_exists($response, 'gender')) ? $response->gender : "";
		$this->user->profile->language = (property_exists($response, 'locale')) ? $response->locale : "";
		$this->user->profile->email = (property_exists($response, 'email')) ? $response->email : "";
		$this->user->profile->emailVerified = (property_exists($response, 'email_verified')) ? ($response->email_verified === true || $response->email_verified === 1 ? $response->email : "") : "";

		return $this->user->profile;
	}

	/**
	 * {@inheritdoc}
	 */
	function getUserContacts() {
		// refresh tokens if needed
		$this->refreshToken();
		
		$contacts = array();
		if (!isset($this->config['contacts_param'])) {
			$this->config['contacts_param'] = array("max-results" => 500);
		}
		
		// Google Gmail and Android contacts
		if (strpos($this->scope, '/m8/feeds/') !== false) {
			
			$response = $this->api->api("https://www.google.com/m8/feeds/contacts/default/full?"
					. http_build_query(array_merge(array('alt' => 'json'), $this->config['contacts_param'])));
			
			if (!$response) {
				return array();
			}
			
			if (isset($response->feed->entry)) {
				foreach ($response->feed->entry as $idx => $entry) {
					$uc = new Hybrid_User_Contact();
					$uc->email = isset($entry->{'gd$email'}[0]->address) ? (string) $entry->{'gd$email'}[0]->address : '';
					$uc->displayName = isset($entry->title->{'$t'}) ? (string) $entry->title->{'$t'} : '';
					$uc->identifier = ($uc->email != '') ? $uc->email : '';
					$uc->description = '';
					if (property_exists($entry, 'link')) {
						/**
						 * sign links with access_token
						 */
						if (is_array($entry->link)) {
							foreach ($entry->link as $l) {
								if (property_exists($l, 'gd$etag') && $l->type == "image/*") {
									$uc->photoURL = $this->addUrlParam($l->href, array('access_token' => $this->api->access_token));
								} else if ($l->type == "self") {
									$uc->profileURL = $this->addUrlParam($l->href, array('access_token' => $this->api->access_token));
								}
							}
						}
					} else {
						$uc->profileURL = '';
					}
					if (property_exists($response, 'website')) {
						if (is_array($response->website)) {
							foreach ($response->website as $w) {
								if ($w->primary == true)
									$uc->webSiteURL = $w->value;
							}
						} else {
							$uc->webSiteURL = $response->website->value;
						}
					} else {
						$uc->webSiteURL = '';
					}
					
					$contacts[] = $uc;
				}
			}
		}
		
		return $contacts;
	}
	
	/**
	 * Add query parameters to the $url
	 *
	 * @param string $url    URL
	 * @param array  $params Parameters to add
	 * @return string
	 */
	function addUrlParam($url, array $params){		
		$query = parse_url($url, PHP_URL_QUERY);

		// Returns the URL string with new parameters
		if ($query) {
			$url .= '&' . http_build_query($params);
		} else {
			$url .= '?' . http_build_query($params);
		}
		return $url;
	}

}

