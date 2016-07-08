<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Providers_Dataporten provider adapter based on OAuth2 protocol
 *
 */
class Hybrid_Providers_Dataporten extends Hybrid_Provider_Model_OAuth2 {

	/**
	 * > more infos on dataporten APIs: https://dashboard.dataporten.no (official site)
	 * default permissions
	 * {@inheritdoc}
	 */

	/**
	 * {@inheritdoc}
	 */
	function initialize() {
		parent::initialize();

		// Provider api end-points
		$this->api->authorize_url = "https://auth.dataporten.no/oauth/authorization";
		$this->api->token_url = "https://auth.dataporten.no/oauth/token";
		$this->api->userinfo_url = "https://auth.dataporten.no/userinfo";

		// Dataporten GET methods require an access_token in the header
		$this->api->curl_header = array("Authorization: Bearer " . $this->api->access_token);
	}

	/*
	 * {@inheritdoc}
	 */
	function loginBegin() {
		$parameters = array();
		$optionals = array("redirect_uri");

		foreach ($optionals as $parameter) {
			if (isset($this->config[$parameter]) && !empty($this->config[$parameter])) {
				$parameters[$parameter] = $this->config[$parameter];
			}
		}


		Hybrid_Auth::redirect($this->api->authorizeUrl($parameters));
	}

	/**
	 * {@inheritdoc}
	 */
	function getUserProfile() {
		$response = json_decode(json_encode($this->api->api($this->api->userinfo_url)),true);

		//$this->user->profile->identifier = ($response["user"]) ? $response["user"]["userid"] : (($response, "userid")) ? $response["userid"] : "";

		if($response["user"]) {
			$this->user->profile->identifier 		= ($response["user"]) ? $response["user"]["userid"] : "";
			$this->user->profile->firstName 		= ($response["user"]["name"]) ? $this->get_name_part($response["user"]["name"], 0) : "";
			$this->user->profile->lastName 			= ($response["user"]["name"]) ? $this->get_name_part($response["user"]["name"], 1) : "";
			$this->user->profile->displayName 	= ($response["user"]["name"]) ? $response["user"]["name"] : "";
			$this->user->profile->photoURL 			= ($response["user"]["profilephoto"]) ? "https://api.dataporten.no/userinfo/v1/user/media/" . $response["user"]["profilephoto"] : "";
			$this->user->profile->email 				= ($response["user"]["email"]) ? $response["user"]["email"] : "";
			$this->user->profile->emailVerified = ($response["user"]["email"]) ? $response["user"]["email"] : "";
		} else if($response["name"]) {
			$this->user->profile->identifier 		= ($response["userid"]) ? $response["userid"] : "";
			$this->user->profile->firstName 		= ($response["name"]) ? $this->get_name_part($response["name"], 0) : "";
			$this->user->profile->lastName 			= ($response["name"]) ? $this->get_name_part($response["name"], 1) : "";
			$this->user->profile->displayName 	= ($response["name"]) ? $response["name"] : "";
		} else {
			throw new Exception("User profile request failed! {$this->providerId} returned an invalid response:" . Hybrid_Logger::dumpData( $response ), 6);
		}
		return $this->user->profile;
	}

	function get_name_part($fullname, $part) {
		$explode = explode(' ', $fullname); // split all parts

		$end = '';
		$begin = '';

		if(count($explode) > 0){
		    $end = array_pop($explode); // removes the last element, and returns it

		    if(count($explode) > 0){
		        $begin = implode(' ', $explode); // glue the remaining pieces back together
		    }
		}
		return $part === 1 ? $end : $begin;
	}

}
