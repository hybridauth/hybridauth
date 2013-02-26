<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Api\OAuth1;

use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthConsumer;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthDataStore;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthExceptionPHP;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthRequest;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthServer;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthSignatureMethod;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthSignatureMethodHMACSHA1;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthToken;
use Hybridauth\Adapter\Api\OAuth1\OAuthLib\OAuthUtil;

class Api // implements \Hybridauth\Adapter\Api\ApiInterface
{
	public $application = null;
	public $endpoints   = null;
	public $tokens      = null;
	public $httpClient  = null;

	private $_oauthLibSha1Method = null;
	private $_oauthLibConsumer   = null;
	private $_oauthLibTokens     = null;

	// --------------------------------------------------------------------

	public function __construct()
	{
		$this->application = new \Hybridauth\Adapter\Api\Application();
		$this->endpoints   = new \Hybridauth\Adapter\Api\Endpoints();
		$this->tokens      = new \Hybridauth\Adapter\Api\OAuth1\Tokens(); 
		$this->httpClient  = new \Hybridauth\Http\Client();
	}

	// --------------------------------------------------------------------

	public function initialize()
	{
		$this->_oauthLibSha1Method = new OAuthSignatureMethodHMACSHA1();
		$this->_oauthLibConsumer   = new OAuthConsumer( $this->application->key, $this->application->secret );

		if( $this->tokens->accessToken ){
			$this->_oauthLibTokens = new OAuthConsumer( $this->tokens->accessToken, $this->tokens->accessSecretToken );
		}
		elseif( $this->tokens->requestToken ){
			$this->_oauthLibTokens = new OAuthConsumer( $this->tokens->requestToken, $this->tokens->requestSecretToken );
		}
	}

	// --------------------------------------------------------------------

	public function requestAuthToken()
	{
		$parameters = array( 'oauth_callback' => $this->endpoints->redirectUri );

		// fixme!
		$response = $this->_signedRequest( $this->endpoints->requestTokenUri, 'GET', $parameters ); 

		$tokens  = OAuthUtil::parse_parameters( $response );

		$this->tokens->requestToken       = $tokens['oauth_token'];
		$this->tokens->requestSecretToken = $tokens['oauth_token_secret'];

		$this->_oauthLibTokens = new OAuthConsumer( $this->tokens->requestToken, $this->tokens->requestSecretToken );
	}

	// --------------------------------------------------------------------

	public function requestAccessToken()
	{
		$parameters = array();

		// 1.0a
		if ( $this->tokens->oauthVerifier ) {
			$parameters['oauth_verifier'] = $this->tokens->oauthVerifier; 
		}

		// fixme!
		$request = $this->_signedRequest( $this->endpoints->accessTokenUri, 'GET', $parameters ); 

		$tokens = OAuthUtil::parse_parameters( $request ); 

		$this->tokens->accessToken       = $tokens['oauth_token'];
		$this->tokens->accessSecretToken = $tokens['oauth_token_secret'];

		$this->_oauthLibTokens = new OAuthConsumer( $this->tokens->accessToken, $this->tokens->accessSecretToken );
	}

	// --------------------------------------------------------------------

	private function _signedRequest( $uri, $method, $parameters )
	{
		if ( strrpos($uri, 'http://') !== 0 && strrpos($uri, 'https://') !== 0 ){
			$uri = $this->endpoints->baseUri . $uri;
		}

		$request = OAuthRequest::from_consumer_and_token( $this->_oauthLibConsumer, $this->_oauthLibTokens, $method, $uri, $parameters );
		$request->sign_request( $this->_oauthLibSha1Method, $this->_oauthLibConsumer, $this->_oauthLibTokens );

		// fixme!
		$this->httpClient->get( $request->to_url() );

		// fixme!
		// default ha client uses curl
		if( $this->httpClient->getResponseError() ){
			throw new
				\Hybridauth\Exception(
					"Provider returned and error. CURL Error (" . $this->httpClient->getResponseError() . "). For more information refer to http://curl.haxx.se/libcurl/c/libcurl-errors.html",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		if( $this->httpClient->getResponseStatus() != 200 ){
			throw new
				\Hybridauth\Exception(
					"Provider returned and error. HTTP Error (" . $this->httpClient->getResponseStatus() . ")",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		return $this->httpClient->getResponseBody();
	}

	// --------------------------------------------------------------------

	function generateAuthorizeUri( $extras = array() )
	{
		$parameters = array( "oauth_token" => $this->tokens->requestToken );

		if( count( $extras ) ){
			foreach( $extras as $k => $v ){
				$parameters[$k] = $v;
			}
		}

		return $this->endpoints->authorizeUri . "?" . http_build_query( $parameters );
    }

	// --------------------------------------------------------------------

	public function get( $uri, $args = array() ) 
	{
		return $this->_signedRequest( $uri, 'GET', $args );
	}

	// --------------------------------------------------------------------

	public function post( $uri, $args = array() ) 
	{
		return $this->_signedRequest( $uri, 'POST', $args );
	}
}
