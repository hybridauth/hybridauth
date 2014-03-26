<?php
/*!
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Providers_DrupalOAuth2 provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_DrupalOAuth2 extends Hybrid_Provider_Model_OAuth2
{
  // default permissions
  public $scope = 'user_profile';

  // The 'state' variable helps to prevent CSRF attacks,
  // and can also be used to identify the authentication request.
  protected $state = NULL;

  /**
   * IDp wrappers initializer
   */
  function initialize()
  {
    parent::initialize();

    $base_url = $this->config['oauth2_server'];
    $this->api->api_base_url   = $base_url;
    $this->api->authorize_url  = $base_url . '/oauth2/authorize';
    $this->api->token_url      = $base_url . '/oauth2/token';

    if (isset($this->config['redirect_uri'])) {
      $this->api->redirect_uri = $this->config['redirect_uri'];
    }

    if (isset($this->config['scope'])) {
      $this->scope = $this->config['scope'];
    }
    if (isset($this->config['state'])) {
      $this->state = $this->config['state'];
    }

    if ($this->config['skip_ssl']) {
      $this->api->curl_ssl_verifypeer = FALSE;
      $this->api->curl_ssl_verifyhost = FALSE;
    }
    if (isset($this->config['http_proxy']) and $this->config['http_proxy']) {
      $this->api->curl_proxy = $this->config['http_proxy'];
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
    $session_var_name = 'state_' . $this->api->client_id;
    $_SESSION[$session_var_name] = $this->state;
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
    $session_var_name = 'state_' . $this->api->client_id;
    if (isset($_SESSION[$session_var_name])) {
      $state = $_SESSION[$session_var_name];
    }
    if (!isset($state) || !isset($_REQUEST['state'])
	|| $state !== $_REQUEST['state']) {
      throw new Exception('Authentication failed! CSRF state token does not match the one provided.');
    }
    unset($_SESSION[$session_var_name]);

    // call the parent function
    parent::loginFinish();
  }

  /**
   * set proper headers before posting
   */
  function post($url) {
    $this->api->curl_header =
      array(
	    'Authorization: Bearer ' . $this->api->access_token,
	    'Content-Type: application/x-www-form-urlencoded',
	    'Accept: application/json',
	    );
    $response = $this->api->post($url);
    return $response;
  }

  /**
   * load the user profile from the IDp api client
   */
  function getUserProfile()
  {
    // refresh tokens if needed
    $this->refreshToken();

    // get user profile
    $response = $this->post('/oauth2/user/profile');
    if (!isset($response->uid)) {
      throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
    }

    // match the fields of the returned data with
    // the standard fields of the hybridauth profile
    $this->user->profile->identifier    = (property_exists($response,'uid'))?$response->uid:"";
    $this->user->profile->displayName   = (property_exists($response,'name'))?$response->name:"";
    $this->user->profile->photoURL      = (property_exists($response,'picture'))?$response->picture:"";
    $this->user->profile->email         = (property_exists($response,'mail'))?$response->mail:"";
    $this->user->profile->emailVerified = (property_exists($response,'mail'))?$response->mail:"";
    $this->user->profile->language      = (property_exists($response,'language'))?$response->language:"";

    // pass as well all the returned data
    // on an extra field called 'remote_profile'
    $this->user->profile->remote_profile = $response;

    return $this->user->profile;
  }
}
