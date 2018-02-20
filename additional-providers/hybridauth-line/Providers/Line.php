<?php

/**
 * Hybrid_Providers_Line provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Line extends Hybrid_Provider_Model_OAuth2 {

	// default permissions
	public $scope = "P";

	/**
	 * {@inheritdoc}
	 */
	function initialize() {
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.line.me/v2/";
		$this->api->authorize_url = "https://access.line.me/oauth2/v2.1/authorize";
		$this->api->token_url     = "https://api.line.me/oauth2/v2.1/token";

    // Override the redirect uri when it's set in the config parameters. This way we prevent
		// redirect uri mismatches when authenticating with LINE.
		if (isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])) {
			$this->api->redirect_uri = $this->config['redirect_uri'];
    }
    if (isset($this->config['scope']) and !empty($this->config['scope'])) {
      $this->scope = $this->config['scope'];
    }
    if (isset($this->config['state']) and !empty($this->config['state'])) {
      $this->state = $this->config['state'];
    } else {
      $this->state = "1"; // DUMMY STATE
    }

    // LINE require an access_token in the header
    if ( $this->token("access_token") ) {
      $this->api->curl_header[] = 'Authorization: Bearer ' . $this->token( "access_token" );
      $this->api->curl_header[] = 'Content-Type: application/json';
    }
	}

	/**
	* {@inheritdoc}
	*/
	function loginBegin() {
		// redirect the user to the provider authentication url
		Hybrid_Auth::redirect($this->api->authorizeUrl(array("scope" => $this->scope, "state" => $this->state)));
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile(){
		$data = $this->api->api("profile" );

		if ( ! isset( $data->userId ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->userId;
		$this->user->profile->displayName = @ $data->displayName;
		$this->user->profile->description = @ $data->statusMessage;
		$this->user->profile->photoURL    = @ $data->pictureUrl;

		$this->user->profile->webSiteURL  = "";

		$this->user->profile->username    = @ $data->displayName;

		return $this->user->profile;
	}
}
