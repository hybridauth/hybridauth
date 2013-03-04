<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Authentication\OAuth1;

// for now Hybridauth is using OAuth lib as is
// => but should we use all of these, or should we reinvent the wheel
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthConsumer;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthDataStore;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthExceptionPHP;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthRequest;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthServer;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthSignatureMethod;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthSignatureMethodHMACSHA1;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthToken;
use Hybridauth\Adapter\Authentication\OAuth1\OAuthLib\OAuthUtil;

class Template implements \Hybridauth\Adapter\AuthenticationInterface {
	public $application = null;
	public $endpoints = null;
	public $tokens = null;
	public $httpClient = null;
	private $_oauthLibSha1Method = null;
	private $_oauthLibConsumer = null;
	private $_oauthLibTokens = null;
	
	// --------------------------------------------------------------------
	function __construct() {
		$this->application = new \Hybridauth\Adapter\Authentication\OAuth1\Application ();
		$this->endpoints = new \Hybridauth\Adapter\Authentication\OAuth1\Endpoints ();
		$this->tokens = new \Hybridauth\Adapter\Authentication\OAuth1\Tokens ();
		$this->httpClient = new \Hybridauth\Http\Client ();
	}
	
	// --------------------------------------------------------------------
	function initialize($options = array()) {
		// consumer credentials
		if (! $this->config ["keys"] ["key"] || ! $this->config ["keys"] ["secret"]) {
			throw new \Hybridauth\Exception ( "Application credentials are missing", \Hybridauth\Exception::MISSING_APPLICATION_CREDENTIALS );
		}
		
		$this->application->key = $this->config ["keys"] ["key"];
		$this->application->secret = $this->config ["keys"] ["secret"];
		
		// http client
		if (isset ( $this->hybridauthConfig ["http_client"] ) && $this->hybridauthConfig ["http_client"]) {
			$this->httpClient = new $this->hybridauthConfig ["http_client"] ();
		} else {
			$curl_options = isset ( $this->hybridauthConfig ["curl_options"] ) ? $this->hybridauthConfig ["curl_options"] : array ();
			
			$this->httpClient = new \Hybridauth\Http\Client ( $curl_options );
		}
		
		// tokens
		$tokens = $this->getStoredTokens ( $this->tokens );
		
		if ($tokens) {
			$this->tokens = $tokens;
		}
		
		// end-points
		$this->endpoints->redirectUri = $this->hybridauthEndpoint;
		$this->endpoints->baseUri = isset ( $options ['api_base_uri'] ) ? $options ['api_base_uri'] : '';
		$this->endpoints->authorizeUri = isset ( $options ['authorize_uri'] ) ? $options ['authorize_uri'] : '';
		$this->endpoints->requestTokenUri = isset ( $options ['request_token_uri'] ) ? $options ['request_token_uri'] : '';
		$this->endpoints->accessTokenUri = isset ( $options ['access_token_uri'] ) ? $options ['access_token_uri'] : '';
		
		$this->endpoints->authorizeUriParameters = isset ( $options ['authorize_uri_args'] ) ? $options ['authorize_uri_args'] : array ();
		
		// oauth1 lib
		$this->_oauthLibSha1Method = new OAuthSignatureMethodHMACSHA1 ();
		$this->_oauthLibConsumer = new OAuthConsumer ( $this->application->key, $this->application->secret );
		
		if ($this->tokens->accessToken) {
			$this->_oauthLibTokens = new OAuthConsumer ( $this->tokens->accessToken, $this->tokens->accessSecretToken );
		} elseif ($this->tokens->requestToken) {
			$this->_oauthLibTokens = new OAuthConsumer ( $this->tokens->requestToken, $this->tokens->requestSecretToken );
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * begin login step
	 */
	function loginBegin() {
		$this->requestAuthToken ();
		
		if (! $this->tokens || ! $this->tokens->requestToken) {
			throw new \Hybridauth\Exception ( "Authentication failed! {$this->providerId} returned invalid oauth_token", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}
		
		// store tokens
		$this->storeTokens ( $this->tokens );
		
		$parameters = $this->endpoints->authorizeUriParameters;
		$optionals = isset ( $this->config ["authorize_uri_args"] ) ? $this->config ["authorize_uri_args"] : array ();
		$parameters = array_merge ( $parameters, ( array ) $optionals );
		
		$url = $this->generateAuthorizeUri ( $parameters );
		
		\Hybridauth\Http\Util::redirect ( $url );
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * finish login step
	 */
	function loginFinish($code = null, $parameters = array(), $method = 'POST') {
		$oauth_token = (array_key_exists ( 'oauth_token', $_REQUEST )) ? $_REQUEST ['oauth_token'] : "";
		$oauth_verifier = (array_key_exists ( 'oauth_verifier', $_REQUEST )) ? $_REQUEST ['oauth_verifier'] : "";
		
		if (! $oauth_token || ! $oauth_verifier) {
			throw new \Hybridauth\Exception ( "Authentication failed! {$this->providerId} returned an invalid oauth verifier", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}
		
		if ($oauth_verifier) {
			$this->tokens->oauthVerifier = $oauth_verifier;
		}
		
		$this->requestAccessToken ();
		
		// check if authenticated
		if (! $this->tokens->accessToken) {
			throw new \Hybridauth\Exception ( "Authentication failed! {$this->providerId} returned an invalid access token", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}
		
		// store tokens
		$this->storeTokens ( $this->tokens );
	}
	
	// --------------------------------------------------------------------
	function generateAuthorizeUri($parameters = array()) {
		$defaults = array (
				"oauth_token" => $this->tokens->requestToken 
		);
		
		$parameters = array_merge ( $defaults, ( array ) $parameters );
		
		return $this->endpoints->authorizeUri . "?" . http_build_query ( $parameters );
	}
	
	// --------------------------------------------------------------------
	function getStoredTokens() {
		return $this->storage->get ( "{$this->providerId}.tokens" );
	}
	
	// --------------------------------------------------------------------
	function storeTokens(Hybridauth\Adapter\Authentication\OAuth1\TokensInterface $tokens) {
		$this->storage->set ( "{$this->providerId}.tokens", $tokens );
	}
	
	// --------------------------------------------------------------------
	function isAuthorized() {
		return $this->tokens->accessToken != null;
	}
	
	// --------------------------------------------------------------------
	function requestAuthToken($parameters = array(), $method = 'GET') {
		$defaults = array (
				'oauth_callback' => $this->endpoints->redirectUri 
		);
		
		$parameters = array_merge ( $defaults, ( array ) $parameters );
		
		$response = $this->_signedRequest ( $this->endpoints->requestTokenUri, $method, $parameters );
		
		$tokens = OAuthUtil::parse_parameters ( $response );
		
		$this->tokens->requestToken = $tokens ['oauth_token'];
		$this->tokens->requestSecretToken = $tokens ['oauth_token_secret'];
		
		$this->_oauthLibTokens = new OAuthConsumer ( $this->tokens->requestToken, $this->tokens->requestSecretToken );
	}
	
	// --------------------------------------------------------------------
	function requestAccessToken($parameters = array(), $method = 'GET') {
		$defaults = array ();
		
		if ($this->tokens->oauthVerifier) {
			$defaults ['oauth_verifier'] = $this->tokens->oauthVerifier;
		}
		
		$parameters = array_merge ( $defaults, ( array ) $parameters );
		
		$request = $this->_signedRequest ( $this->endpoints->accessTokenUri, $method, $parameters );
		
		$tokens = OAuthUtil::parse_parameters ( $request );
		
		$this->tokens->accessToken = $tokens ['oauth_token'];
		$this->tokens->accessSecretToken = $tokens ['oauth_token_secret'];
		
		$this->_oauthLibTokens = new OAuthConsumer ( $this->tokens->accessToken, $this->tokens->accessSecretToken );
	}
	
	// --------------------------------------------------------------------
	function get($uri, $parameters = array()) {
		return $this->_signedRequest ( $uri, 'GET', $parameters );
	}
	
	// --------------------------------------------------------------------
	function post($uri, $parameters = array()) {
		return $this->_signedRequest ( $uri, 'POST', $parameters );
	}
	
	// --------------------------------------------------------------------
	function _signedRequest($uri, $method = 'GET', $parameters = array(), $authHeader = true /* <= fixme! */ )
	{
		if (strrpos ( $uri, 'http://' ) !== 0 && strrpos ( $uri, 'https://' ) !== 0) {
			$uri = $this->endpoints->baseUri . $uri;
		}
		
		$request = OAuthRequest::from_consumer_and_token ( $this->_oauthLibConsumer, $this->_oauthLibTokens, $method, $uri, $parameters );
		$request->sign_request ( $this->_oauthLibSha1Method, $this->_oauthLibConsumer, $this->_oauthLibTokens );
		
		// fixme!
		$this->httpClient->get ( $request->to_url () );
		
		// fixme!
		// default ha client uses curl
		if ($this->httpClient->getResponseError ()) {
			throw new \Hybridauth\Exception ( "Provider returned and error. CURL Error (" . $this->httpClient->getResponseError () . "). For more information refer to http://curl.haxx.se/libcurl/c/libcurl-errors.html", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}
		
		if ($this->httpClient->getResponseStatus () != 200) {
			throw new \Hybridauth\Exception ( "Provider returned and error. HTTP Error (" . $this->httpClient->getResponseStatus () . ")", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}
		
		return $this->httpClient->getResponseBody ();
	}
}
