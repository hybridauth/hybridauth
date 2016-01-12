<?php

/**
 * Hybrid_Providers_DigitalOcean - DigitalOcean provider adapter based on the OAuth2 protocol.
 */
class Hybrid_Providers_DigitalOcean extends Hybrid_Provider_Model_OAuth2
{
	// default permissions
	// (read write) => Grants read/write access to user account, i.e. full access. This allows actions that can be requested using the DELETE, PUT, and POST methods, in addition to the actions allowed by the read scope.
	public $scope = "read write";

	/**
	* IDp wrappers initializer
	*/
	function initialize()
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.digitalocean.com/";
		$this->api->authorize_url = "https://cloud.digitalocean.com/v1/oauth/authorize";
		$this->api->token_url     = "https://cloud.digitalocean.com/v1/oauth/token";
		// Override the redirect uri when it's set in the config parameters. This way we prevent
		// redirect uri mismatches when authenticating with DigitalOcean.
		if (isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])) {
			$this->api->redirect_uri = $this->config['redirect_uri'];
		}
    if (isset($this->config['scope']) and !empty($this->config['scope'])) {
      $this->scope = $this->config['scope'];
    }
    if (isset($this->config['state']) and !empty($this->config['state'])) {
      $this->state = $this->config['state'];
    }
		if( $this->token( "access_token" ) ){
			$this->api->curl_header[] = 'Authorization: Bearer ' . $this->token( "access_token" );
      $this->api->curl_header[] = 'Content-Type: application/json';
		}
	}

  /**
   * begin login step
   */
  function loginBegin()
  {
    if (!isset($this->state)) {
      $this->state = md5(uniqid(rand(), TRUE));
    }
    $this->saveState($this->state);
    $extra_params['state'] = $this->state;

    if (isset($this->scope)) {
      $extra_params['scope'] = $this->scope;
    }

    Hybrid_Auth::redirect($this->api->authorizeUrl($extra_params));
  }

  /**
   * finish login step
   */
  function loginFinish()
  {
    // check that the CSRF state token is the same as the one provided
    $this->checkState();

    // call the parent function
    parent::loginFinish();
  }

  /**
   * Save the given $state in session.
   */
  protected function saveState($state) {
    $session_var_name = 'state_' . $this->api->client_id;
    $_SESSION['HybridAuth']['DigitalOcean'][$session_var_name] = $state;
  }

  /**
   * Read the state from session.
   */
  protected function readState() {
    $session_var_name = 'state_' . $this->api->client_id;
    $state = ( isset($_SESSION['HybridAuth']['DigitalOcean'][$session_var_name])
	      ? $_SESSION['HybridAuth']['DigitalOcean'][$session_var_name]
	      : NULL );
    unset($_SESSION['HybridAuth']['DigitalOcean'][$session_var_name]);
    return $state;
  }

  /**
   * Check the state in the request against the one saved in session.
   */
  protected function checkState() {
    $state = $this->readState();
    if (!$state || !isset($_REQUEST['state']) || $state != $_REQUEST['state'])
      {
	throw new Exception('Authentication failed! CSRF state token does not match the one provided.');
      }
  }

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed
		$this->refreshToken();

		$data = $this->api->api( "v2/account" );

		if ( ! isset( $data->account->uuid ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->account->uuid;
		$this->user->profile->displayName = @ $data->account->email; // No display name value from the API so we use email
		$this->user->profile->email       = @ $data->account->email;
		//$this->user->profile->region      = @ $data->account->location;

    // Digital ocean returns a flag marking the email as verified or not
    // We compare this to the email in use and set the emailVerified
    // value accodringly.
    if (isset($data->account->email_verified) and TRUE == $data->account->email_verified and $data->account->email == $data->account->email_verified) {
      $this->user->profile->emailVerified = $data->account->email;
    }

		return $this->user->profile;
	}
}
