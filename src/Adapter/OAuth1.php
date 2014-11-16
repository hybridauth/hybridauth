<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

use Hybridauth\Data; 
use Hybridauth\HttpClient;
use Hybridauth\Exception;

use Hybridauth\Thirdparty\OAuth\OAuthConsumer;
use Hybridauth\Thirdparty\OAuth\OAuthDataStore;
use Hybridauth\Thirdparty\OAuth\OAuthExceptionPHP;
use Hybridauth\Thirdparty\OAuth\OAuthRequest;
use Hybridauth\Thirdparty\OAuth\OAuthServer;
use Hybridauth\Thirdparty\OAuth\OAuthSignatureMethod;
use Hybridauth\Thirdparty\OAuth\OAuthSignatureMethodHMACSHA1;
use Hybridauth\Thirdparty\OAuth\OAuthToken;
use Hybridauth\Thirdparty\OAuth\OAuthUtil;

/**
 * To implement an OAuth 1 based service provider, Hybrid_Provider_Model_OAuth2
 * can be used to save the hassle of the authentication flow.
 */
abstract class OAuth1 extends AdapterBase implements AdapterInterface 
{
	/**
	* Base URL to provider API
	* 
	* This var will be used to build urls when sending signed requests
	*
	* @var string
	*/
	protected $apiBaseUrl = '';

	/**
	* @var string
	*/
	protected $authorizeUrl = '';

	/**
	* @var string
	*/
	protected $requestTokenUrl = '';

	/**
	* @var string
	*/
	protected $accessTokenUrl = '';

	/**
	* OAuth Version
	*
	*  '1.0' OAuth Core 1.0
	* '1.0a' OAuth Core 1.0 Revision A
	*
	* @var string
	*/
	protected $oauth1Version = '1.0a';

	/**
	* @var object
	*/
	protected $consumerKey = null;

	/**
	* @var object
	*/
	protected $sha1Method = null;

	/**
	* @var object
	*/
	protected $consumerToken = null;

	/**
	* @var string
	*/
	protected $requestTokenMethod = 'POST';

	/**
	* @var array
	*/
	protected $requestTokenParameters = array();

	/**
	* @var array
	*/
	protected $requestTokenHeaders = array();

	/**
	* @var string
	*/
	protected $tokenExchangeMethod = 'POST';

	/**
	* @var array
	*/
	protected $tokenExchangeParameters = array();

	/**
	* @var array
	*/
	protected $tokenExchangeHeaders = array();

	/**
	* @var array
	*/
	protected $apiRequestParameters = array();

	/**
	* @var array
	*/
	protected $apiRequestHeaders = array();

	/**
	* Adapter initializer
	*
	* @throws Exception
	*/
	protected function initialize()
	{
		if( ! $this->config->filter( 'keys' )->get( 'key' ) || ! $this->config->filter( 'keys' )->get( 'secret' ) )
		{
			throw new Exception( "Your consumer key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		if( $this->config->exists( 'tokens' ) )
		{
			$this->setAccessToken( $this->config->get( 'tokens' ) );
		}

		/**
		* Set up OAuth Signature and Consumer
		* 
		* OAuth Core: All Token requests and Protected Resources requests MUST be signed
		* by the Consumer and verified by the Service Provider. 
		*
		* The protocol defines three signature methods: HMAC-SHA1, RSA-SHA1, and PLAINTEXT..
		*
		* The Consumer declares a signature method in the oauth_signature_method parameter..
		* 
		* http://oauth.net/core/1.0a/#signing_process
		*/
		$this->sha1Method  = new OAuthSignatureMethodHMACSHA1();
		$this->consumerKey = new OAuthConsumer( $this->config->filter( 'keys' )->get( 'key' ), $this->config->filter( 'keys' )->get( 'secret' ) );

		if( $this->token( 'request_token' ) )
		{
			$this->consumerToken = new OAuthConsumer( $this->token( 'request_token' ), $this->token( 'request_token_secret' ) );
		}

		if( $this->token( 'access_token' ) )
		{
			$this->consumerToken = new OAuthConsumer( $this->token( 'access_token' ), $this->token( 'access_token_secret' ) );
		}

		$endpoints = $this->config->filter( 'endpoints' );

		$this->apiBaseUrl     = $endpoints->exists( 'api_base_url'     ) ? $endpoints->get( 'api_base_url'     ) : $this->apiBaseUrl     ;
		$this->authorizeUrl   = $endpoints->exists( 'authorize_url'    ) ? $endpoints->get( 'authorize_url'    ) : $this->authorizeUrl   ;
		$this->accessTokenUrl = $endpoints->exists( 'access_token_url' ) ? $endpoints->get( 'access_token_url' ) : $this->accessTokenUrl ;
	}

	function authenticate()
	{
		if( $this->isAuthorized() )
		{
			return true;
		}

		try
		{
			if( ! $this->token( 'request_token' ) )
			{
				return $this->authenticateBegin();
			}

			if( ! $this->token( 'access_token' ) )
			{
				return $this->authenticateFinish();
			}
		}
		catch( Exception $e )
		{
			$this->clearTokens();

			throw $e;
		}
	}

	function isAuthorized()
	{
		return (bool) $this->token( 'access_token' );
	}

	function disconnect()
	{
		$this->clearTokens();

		return true;
	}

	/**
	* Initiate the authorization protocol
	*
	* 1. Obtaining an Unauthorized Request Token
	* 2. Build Authorization URL for Authorization Request and redirect the user-agent to the
	* Authorization Server.
	*
	* Sub classes may redefine this method when necessary.
	*
	* See requestAuthToken()
	* See getAuthorizeUrl()
	* See Hybrid_Auth::redirect()
	*/
	function authenticateBegin()
	{
		$response = $this->requestAuthToken();

		$this->validateAuthTokenRequest( $response );

		$authUrl = $this->getAuthorizeUrl();

		HttpClient\Util::redirect( $authUrl );
	}

	/**
	* ..
	*/
	function authenticateFinish()
	{
		$data = new Data\Collection( $_GET );

		$denied         = $data->get( 'denied'         );
		$oauth_problem  = $data->get( 'oauth_problem'  );
		$oauth_token    = $data->get( 'oauth_token'    );
		$oauth_verifier = $data->get( 'oauth_verifier' );

		if( $denied )
		{
			throw new Exception( "Authentication denied! {$this->providerId} returned denied token: " . htmlentities( $denied ), 5 );
		}

		if( $oauth_problem )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an error, oauth_problem: " . htmlentities( $oauth_problem ), 5 );
		}

		if( ! $oauth_token )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth_token.", 5 );
		}

		$response = $this->exchangeAuthTokenForAccessToken( $oauth_token, $oauth_verifier );
		
		$this->validateAccessTokenExchange( $response );

		$this->initialize();
	}

	/**
	* Build Authorization URL for Authorization Request
	*
	* @param array $parameters
	*
	* @return string
	*/
	protected function getAuthorizeUrl( $parameters = array() )
	{
		$defaults = array( 'oauth_token' => $this->token( 'request_token' ) );

		$parameters = array_replace( $defaults, (array) $parameters );

		return $this->authorizeUrl . '?' . http_build_query( $parameters, '', '&' );
	}

	/**
	* Unauthorized Request Token
	*
	* OAuth Core: The Consumer obtains an unauthorized Request Token by asking the Service Provider
	* to issue a Token. The Request Token's sole purpose is to receive User approval and can only 
	* be used to obtain an Access Token.
	* 
	* http://oauth.net/core/1.0/#auth_step1
	* 6.1.1. Consumer Obtains a Request Token
	*/
	protected function requestAuthToken()
	{
		/**
		* OAuth Core 1.0 Revision A: oauth_callback: An absolute URL to which the Service Provider will redirect
		* the User back when the Obtaining User Authorization step is completed.
		*
		* http://oauth.net/core/1.0a/#auth_step1
		*/
		if( '1.0a' == $this->oauth1Version )
		{
			$this->requestTokenParameters['oauth_callback'] = $this->endpoint;
		}

		$response = $this->oauthRequest( 
			$this->requestTokenUrl, 
			$this->requestTokenMethod, 
			$this->requestTokenParameters, 
			$this->requestTokenHeaders
		);
		
		return $response;
	}

	/**
	* Validate Unauthorized Request Token Response
	*
	* OAuth Core: The Service Provider verifies the signature and Consumer Key. If successful, 
	* it generates a Request Token and Token Secret and returns them to the Consumer in the HTTP
	* response body.
	* 
	* http://oauth.net/core/1.0/#auth_step1 
	* 6.1.2. Service Provider Issues an Unauthorized Request Token
	*
	* @param string $response
	*
	* @throws Exception
	*/
	protected function validateAuthTokenRequest( $response )
	{
		/**
		* The response contains the following parameters:
		*
		*    - oauth_token               The Request Token.
		*    - oauth_token_secret        The Token Secret.
		*    - oauth_callback_confirmed  MUST be present and set to true.
		* 
		* http://oauth.net/core/1.0/#auth_step1 
		* 6.1.2. Service Provider Issues an Unauthorized Request Token
		*
		* Example of a successful response:
		*
		*  HTTP/1.1 200 OK
		*  Content-Type: text/html; charset=utf-8
		*  Cache-Control: no-store
		*  Pragma: no-cache
		*
		*  oauth_token=80359084-clg1DEtxQF3wstTcyUdHF3wsdHM&oauth_token_secret=OIF07hPmJB:P
		*  6qiHTi1znz6qiH3tTcyUdHnz6qiH3tTcyUdH3xW3wsDvV08e&example_parameter=example_value
		*
		* OAuthUtil::parse_parameters will attempt to decode the raw response into an array.
		*/
		$tokens = OAuthUtil::parse_parameters( $response );

		if( ! is_array( $tokens ) || ! isset( $tokens['oauth_token'] ) )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid response: " . htmlentities( $response ), 5 );
		}

		$this->consumerToken = new OAuthConsumer( $tokens['oauth_token'], $tokens['oauth_token_secret'] );

		$this->token( 'request_token'       , $tokens['oauth_token'] );
		$this->token( 'request_token_secret', $tokens['oauth_token_secret'] );
	}

	/**
	* Requests an Access Token
	*
	* OAuth Core: The Request Token and Token Secret MUST be exchanged for an Access Token and Token Secret.
	* 
	* http://oauth.net/core/1.0a/#auth_step3
	* 6.3.1. Consumer Requests an Access Token
	*
	* @param string $oauth_token
	* @param string $oauth_verifier
	*
	* @throws Exception
	*/
	protected function exchangeAuthTokenForAccessToken( $oauth_token, $oauth_verifier = '' )
	{
		$this->tokenExchangeParameters['oauth_token'] = $oauth_token;

		/**
		* OAuth Core 1.0 Revision A: oauth_verifier: The verification code received from the Service Provider 
		* in the "Service Provider Directs the User Back to the Consumer" step.
		*
		* http://oauth.net/core/1.0a/#auth_step3
		*/
		if( '1.0a' == $this->oauth1Version )
		{
			$this->tokenExchangeParameters['oauth_verifier'] = $oauth_verifier;
		}

		$response = $this->oauthRequest( 
			$this->accessTokenUrl,
			$this->tokenExchangeMethod,
			$this->tokenExchangeParameters,
			$this->tokenExchangeHeaders
		);

		return $response;
	}

	/**
	* Validate Access Token Response
	*
	* OAuth Core: If successful, the Service Provider generates an Access Token and Token Secret and returns
	* them in the HTTP response body. 
	*
	* The Access Token and Token Secret are stored by the Consumer and used when signing Protected Resources requests.
	* 
	* http://oauth.net/core/1.0a/#auth_step3
	* 6.3.2. Service Provider Grants an Access Token
	*
	* @param $response
	*
	* @throws Exception
	*/
	protected function validateAccessTokenExchange( $response )
	{
		/**
		* The response contains the following parameters:
		*
		*    - oauth_token         The Access Token.
		*    - oauth_token_secret  The Token Secret.
		* 
		* http://oauth.net/core/1.0/#auth_step3 
		* 6.3.2. Service Provider Grants an Access Token
		*
		* Example of a successful response:
		*
		*  HTTP/1.1 200 OK
		*  Content-Type: text/html; charset=utf-8
		*  Cache-Control: no-store
		*  Pragma: no-cache
		*
		*  oauth_token=sHeLU7Far428zj8PzlWR75&oauth_token_secret=fXb30rzoG&oauth_callback_confirmed=true
		*
		* OAuthUtil::parse_parameters will attempt to decode the raw response into an array.
		*/
		$tokens = OAuthUtil::parse_parameters( $response );

		if( ! is_array( $tokens ) || ! isset( $tokens['oauth_token'] ) )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth_token: " . htmlentities( $response ), 5 );
		}

		$this->consumerToken = new OAuthConsumer( $tokens['oauth_token'], $tokens['oauth_token_secret'] );

		$this->deleteToken( 'request_token'        );
		$this->deleteToken( 'request_token_secret' );

		$this->token( 'access_token'        , $tokens['oauth_token'] );
		$this->token( 'access_token_secret' , $tokens['oauth_token_secret'] );
	}

	/**
	* Send a signed request to provider API
	*
	* @param string $url
	* @param string $method
	* @param array  $parameters
	* @param array  $headers
	* @param string $body
	* @param string $content_type
	* @param bool   $multipart
	*
	* @return object
	*/
	function apiRequest( $url, $method = 'GET', $parameters = array(), $headers = array() )
	{
		if ( strrpos( $url, 'http://' ) !== 0 && strrpos( $url, 'https://' ) !== 0 )
		{
			$url = $this->apiBaseUrl . $url;
		}

		$parameters = array_replace( $this->apiRequestParameters, (array) $parameters );

		$headers = array_replace( $this->apiRequestHeaders, (array) $headers );

		$response = $this->oauthRequest( $url, $method, $parameters, $headers );

		$response = ( new Data\Parser() )->parse( $response );

		return $response;
	}

	/**
	* Setup and Send a Signed Oauth Request
	*
	* This method uses OAuth Library.
	*
	* @param string $uri
	* @param string $method
	* @param array  $parameters
	* @param array  $headers
	* @param string $body
	* @param string $content_type
	* @param bool   $multipart
	*
	* @throws OAuthException
	* @return string Raw Provider API response
	*/
	protected function oauthRequest( $uri, $method = 'GET', $parameters = array(), $headers = array() )
	{
		$request = OAuthRequest::from_consumer_and_token( $this->consumerKey, $this->consumerToken, $method, $uri, $parameters );

		$request->sign_request( $this->sha1Method, $this->consumerKey, $this->consumerToken );

		$response = $this->httpClient->request(
			$request->get_normalized_http_url(), 
			$request->http_method, 
			$request->parameters, 
			$request->to_header()
		);

		$this->validateApiResponse();

		return $response;
	}
}
