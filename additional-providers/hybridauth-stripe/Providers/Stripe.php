<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2017, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Providers_Stripe provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Stripe extends Hybrid_Provider_Model_OAuth2 {

  const DEFAULT_TIMEOUT = 80;
  const DEFAULT_CONNECT_TIMEOUT = 30;
  const DEFAULT_API_VERSION = '2016-07-06';

  private $timeout = self::DEFAULT_TIMEOUT;
  private $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;
  private $apiVersion = self::DEFAULT_API_VERSION;

  /**
   * {@inheritdoc}
   */
  public $scope = 'read_only';
  public $stripe_user_id = NULL;

  /**
   * {@inheritdoc}
   */
  function initialize() {
    parent::initialize();

    // Provider api end-points
    $this->api->authorize_url = "https://connect.stripe.com/oauth/authorize";
    $this->api->token_url = "https://connect.stripe.com/oauth/token";
    $this->api->token_info_url = "https://stripe.com/docs/api";

    // Override the redirect uri when it's set in the config parameters. This way we prevent
    // redirect uri mismatches when authenticating with Stripe.
    if (isset($this->config['redirect_uri']) && !empty($this->config['redirect_uri'])) {
      $this->api->redirect_uri = $this->config['redirect_uri'];
    }
  }

  /**
   * {@inheritdoc}
   */
  function loginBegin() {
    $parameters = array("scope" => $this->scope);
    $optionals = array(
      "scope",
      "access_type",
      "redirect_uri",
    );

    foreach ($optionals as $parameter) {
      if (!empty($this->config[$parameter])) {
        $parameters[$parameter] = $this->config[$parameter];
      }
    }

    if (!empty($this->config["scope"])) {
      $this->scope = $this->config["scope"];
    }

    Hybrid_Auth::redirect($this->api->authorizeUrl($parameters));
  }

  /**
   * {@inheritdoc}
   */
  function loginFinish() {
    $error = (array_key_exists('error', $_REQUEST)) ? $_REQUEST['error'] : "";
    // Check for errors.
    if ($error) {
      throw new Exception("Authentication failed! {$this->providerId} returned an error: $error", 5);
    }
    // Try to authenticate user.
    $code = (array_key_exists('code', $_REQUEST)) ? $_REQUEST['code'] : "";
    try {
      $this->authStripe($code);
    }
    catch (Exception $e) {
      throw new Exception("User profile request failed! {$this->providerId} returned an error: {$e->getMessage()} ", 6);
    }
    // Check if authenticated.
    if (!$this->api->access_token) {
      throw new Exception("Authentication failed! {$this->providerId} returned an invalid access token.", 5);
    }
    // Store tokens.
    $this->token("access_token", $this->api->access_token);
    $this->token("refresh_token", $this->api->refresh_token);
    $this->token("expires_in", $this->api->access_token_expires_in);
    $this->token("expires_at", $this->api->access_token_expires_at);
    // Set user connected locally.
    $this->setUserConnected();
  }

  /**
   * Authenticate user in Stripe.
   *
   * @param string $code
   *   Authorization code.
   *
   * @return mixed|null
   *   Response from Stripe.
   *
   * @throws \Exception
   */
  function authStripe($code) {
    $params = array(
      "client_id" => $this->api->client_id,
      "client_secret" => $this->api->client_secret,
      "grant_type" => "authorization_code",
      "redirect_uri" => $this->api->redirect_uri,
      "code" => $code
    );

    $response = $this->api->api($this->api->token_url, $this->api->curl_authenticate_method, $params);

    if (!$response || !isset($response->access_token)) {
      throw new Exception("The Authorization Service has return: " . $response->error);
    }

    if (isset($response->access_token)) {
      $this->api->access_token = $response->access_token;
    }
    if (isset($response->refresh_token)) {
      $this->api->refresh_token = $response->refresh_token;
    }
    if (isset($response->expires_in)) {
      $this->api->access_token_expires_in = $response->expires_in;
    }

    // Calculate when the access token expire.
    if (isset($response->expires_in)) {
      $this->api->access_token_expires_at = time() + $response->expires_in;
    }
    // At this moment Stripe does not return expire time in response.
    // 5 minutes expire time written in dev docs https://stripe.com/docs/connect/reference#get-authorize-response
    else {
      $this->api->access_token_expires_at = time() + 300;
    }

    // Store Stripe account id. It's required for api access to Stripe.
    if (isset($response->stripe_user_id)) {
      Hybrid_Auth::storage()
        ->set("hauth_session.{$this->providerId}.user_id", $response->stripe_user_id);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  function getUserProfile() {
    // refresh tokens if needed
    $this->refreshToken();
    $stripe_user_id = Hybrid_Auth::storage()
      ->get("hauth_session.{$this->providerId}.user_id");
    $response = $this->profileRequest($stripe_user_id);
    if (!isset($response->id) || isset($response->error)) {
      throw new Exception("User profile request failed! {$this->providerId} returned an invalid response:" . Hybrid_Logger::dumpData($response), 6);
    }

    $this->user->profile->identifier = $response->id;
    $this->user->profile->displayName = isset($response->display_name) ? $response->display_name : "";
    $this->user->profile->email = isset($response->email) ? $response->email : "";
    $this->user->profile->phone = isset($response->support_phone) ? $response->support_phone : "";
    $this->user->profile->country = isset($response->country) ? $response->country : "";

    return $this->user->profile;
  }

  /**
   * {@inheritdoc}
   */
  public function profileRequest($stripe_user_id) {
    $params = array();
    $absUrl = 'https://api.stripe.com/v1/accounts';
    Hybrid_Logger::info("Enter OAuth2Client::request( $absUrl )");
    Hybrid_Logger::debug("OAuth2Client::request(). dump request params: ", serialize($params));

    $langVersion = phpversion();
    $uname = php_uname();
    $ua = array(
      'bindings_version' => $this->apiVersion,
      'lang' => 'php',
      'lang_version' => $langVersion,
      'publisher' => 'stripe',
      'uname' => $uname,
    );

    $default_headers = array(
      'X-Stripe-Client-User-Agent' => json_encode($ua),
      'User-Agent' => 'Stripe/v1 PhpBindings/' . $this->apiVersion,
      'Authorization' => 'Bearer ' . $this->api->access_token,
      'Content-Type' => 'application/x-www-form-urlencoded',
    );

    $headers = array();
    foreach ($default_headers as $header => $value) {
      $headers[] = $header . ': ' . $value;
    }

    $curl = curl_init();
    $opts = array();

    $opts[CURLOPT_HTTPGET] = 1;
    $absUrl = $absUrl . '/' . $stripe_user_id;

    // Create a callback to capture HTTP headers for the response
    $rheaders = array();
    $headerCallback = function ($curl, $header_line) use (&$rheaders) {
      // Ignore the HTTP request line (HTTP/1.1 200 OK)
      if (strpos($header_line, ":") === FALSE) {
        return strlen($header_line);
      }
      list($key, $value) = explode(":", trim($header_line), 2);
      $rheaders[trim($key)] = trim($value);
      return strlen($header_line);
    };

    $opts[CURLOPT_URL] = $absUrl;
    $opts[CURLOPT_RETURNTRANSFER] = TRUE;
    $opts[CURLOPT_CONNECTTIMEOUT] = $this->connectTimeout;
    $opts[CURLOPT_TIMEOUT] = $this->timeout;
    $opts[CURLOPT_RETURNTRANSFER] = TRUE;
    $opts[CURLOPT_HEADERFUNCTION] = $headerCallback;
    $opts[CURLOPT_HTTPHEADER] = $headers;

    // Constant not defined in PHP < 5.5.
    if (!defined('CURL_SSLVERSION_TLSv1')) {
      define('CURL_SSLVERSION_TLSv1', 1);
    }

    $opts[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1;

    curl_setopt_array($curl, $opts);
    $response = curl_exec($curl);
    if ($response === FALSE) {
      Hybrid_Logger::error("OAuth2Client::request(). curl_exec error: ", curl_error($curl));
    }
    Hybrid_Logger::debug("OAuth2Client::request(). dump request info: ", serialize(curl_getinfo($curl)));
    Hybrid_Logger::debug("OAuth2Client::request(). dump request result: ", serialize($response));

    $this->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return json_decode($response);
  }

}
