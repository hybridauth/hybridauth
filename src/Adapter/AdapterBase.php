<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception\UnsupportedFeatureException;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Storage\Session;
use Hybridauth\Logger\LoggerInterface;
use Hybridauth\Logger\Logger;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Curl as HttpClient;
use Hybridauth\Data;

use Hybridauth\Deprecated\DeprecatedAdapterTrait;

/**
 *
 */
abstract class AdapterBase implements AdapterInterface 
{
	use DeprecatedAdapterTrait;

	/**
	* Provider ID (unique name)
	*
	* @var string
	*/
	protected $providerId = '';

	/**
	* Specific Provider config
	*
	* @var mixed
	*/
	protected $config = [];

	/**
	* Extra Provider parameters
	*
	* @var mixed
	*/
	protected $params = [];

	/**
	* Redirection Endpoint (i.e., redirect_uri, callback_url)
	*
	* @var string
	*/
	protected $endpoint = ''; 

	/**
	* Storage
	*
	* @var object
	*/
	public $storage = null;

	/**
	* HttpClient
	*
	* @var object
	*/
	public $httpClient = null;

	/**
	* Logger
	*
	* @var object
	*/
	public $logger = null;

	/**
	* Common adapters constructor
	*
	* @param array               $config
	* @param HttpClientInterface $httpClient
	* @param StorageInterface    $storage
	* @param LoggerInterface     $logger
	*/
	function __construct( $config = [], HttpClientInterface $httpClient = null, StorageInterface $storage = null, LoggerInterface $logger = null )
	{
		$this->providerId = str_replace( 'Hybridauth\\Provider\\', '', get_class($this) ); 

		$this->storage = $storage ? $storage : new Session();

		$this->logger = $logger ? $logger : new Logger( 
			( isset( $config['debug_mode'] ) ? $config['debug_mode'] : false ),
			( isset( $config['debug_file'] ) ? $config['debug_file'] : '' ) 
		);

		$this->httpClient = $httpClient ? $httpClient : new HttpClient();

		if( isset( $config['curl_options'] ) && method_exists( $this->httpClient, 'setCurlOptions' ) ){
			$this->httpClient->setCurlOptions( $this->config['curl_options'] );
		}

		if( method_exists( $this->httpClient, 'setLogger' ) ){
			$this->httpClient->setLogger( $this->logger );
		}

		$this->logger->debug( 'Initialize ' . get_class($this) . '. Provider config: ', $config );

		$this->config = new Data\Collection( $config );

		$this->endpoint = $this->config->get( 'callback' );

		$this->initialize();
	}

	/**
	* Adapter initializer
	*
	* @throws InvalidArgumentException
	* @throws InvalidApplicationCredentialsException
	* @throws InvalidOpenidIdentifierException
	*/
	abstract protected function initialize(); 

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		throw new UnsupportedFeatureException( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* {@inheritdoc}
	*/
	function getUserContacts()
	{
		throw new UnsupportedFeatureException( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* {@inheritdoc}
	*/
	function setUserStatus( $status )
	{
		throw new UnsupportedFeatureException( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* {@inheritdoc}
	*/
	function getUserActivity( $stream )
	{
		throw new UnsupportedFeatureException( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* {@inheritdoc}
	*/
	function apiRequest( $url, $method = 'GET', $parameters = [], $headers = [] )
	{
		throw new UnsupportedFeatureException( 'Provider does not support this feature.', 8 );
	}

	/**
	* Return http client instance
	*
	* @return HttpClient
	*/
	function getHttpClient()
	{
		return $this->httpClient; 
	}
}
