<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth;

use Hybridauth\Exception; 
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Storage\Session;
use Hybridauth\Logger\LoggerInterface;
use Hybridauth\Logger\Logger;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Curl as HttpClient;
use Hybridauth\Provider\ProviderAdapter;

use Hybridauth\Deprecated\DeprecatedHybridauthTrait;

/**
 * The sole purpose for this class is to provide an unified entry point for the various providers
 * and to ensure a MINIMAL backward compatibility with Hybridauth 2.x
 */
class Hybridauth
{
	use DeprecatedHybridauthTrait;

	/**
	* Hybridauth version
	*
	* @var string
	*/
	protected $version = '3.0.0-Remake';

	/**
	* Hybridauth config
	*
	* @var array
	*/
	protected $config = array();

	/**
	* Storage
	*
	* @var object
	*/
	protected $storage = null;

	/**
	* HttpClient
	*
	* @var object
	*/
	protected $httpClient = null;

	/**
	* Logger
	*
	* @var object
	*/
	protected $logger = null;

	/**
	* @param array               $config
	* @param HttpClientInterface $httpClient
	* @param StorageInterface    $storage
	* @param LoggerInterface     $logger
	*
	* @throws Exception
	*/
	function __construct( $config = array(), HttpClientInterface $httpClient = null, StorageInterface $storage = null, LoggerInterface $logger = null )
	{
		if( is_string( $config ) && file_exists( $config ) )
		{
			$config = include $config;
		}
		elseif( ! is_array( $config ) )
		{
			throw new Exception( "Hybriauth config does not exist on the given path.", 1 );
		}

		$this->config = $config;

		$this->storage = $storage ? $storage : new Session();

		$this->logger = $logger ? $logger : new Logger( 
			( isset( $config['debug_mode'] ) ? $config['debug_mode'] : false ),
			( isset( $config['debug_file'] ) ? $config['debug_file'] : '' ) 
		);

		$this->httpClient = $httpClient ? $httpClient : new HttpClient();

		if( isset( $config['curl_options'] ) && method_exists( $this->httpClient, 'setCurlOptions' ) )
		{
			$this->httpClient->setCurlOptions( $this->config['curl_options'] );
		}

		if( method_exists( $this->httpClient, 'setLogger' ) )
		{
			$this->httpClient->setLogger( $this->logger );
		}
	}

	/**
	*
	*/
	function authenticate( $providerId )
	{
		$this->logger->info( "Enter Hybridauth::authenticate( $providerId )" );

		$adapter = $this->getAdapter( $providerId );

		$adapter->authenticate();

		return $adapter;
	}

	/**
	*
	*/
	function getAdapter( $providerId )
	{
		$config = $this->getProviderConfigById( $providerId );

		$adapter = 'Hybridauth\\Provider\\' . $providerId;

		$instance = new $adapter( $config, $this->httpClient, $this->storage, $this->logger );

		return $instance;
	}

	/**
	* Get provider config by ID
	*
	* @param string $id
	*
	* @return array
	*/
	protected function getProviderConfigById( $id )
	{
		$config = [];
		$providerId = $this->validateProviderID( $id );
		$providerConfig = $this->config['providers'][$providerId];

		if( isset( $this->config['callback'] ) )
		{
			$config['callback'] = $this->config['callback'];
		}

		if( isset( $providerConfig['callback'] ) )
		{
			$config['callback'] = $providerConfig['callback'];
		}

		if( isset( $providerConfig['keys']['id'] ) )
		{
			$config['keys']['id'] = $providerConfig['keys']['id'];
		}

		if( isset( $providerConfig['keys']['key'] ) )
		{
			$config['keys']['key'] = $providerConfig['keys']['key'];
		}

		if( isset( $providerConfig['keys']['secret'] ) )
		{
			$config['keys']['secret'] = $providerConfig['keys']['secret'];
		}

		if( isset( $providerConfig['endpoints'] ) )
		{
			$config['endpoints'] = $providerConfig['endpoints'];
		}

		return $config;
	}

	/**
	* Get provider real provider ID. (case sensitive)
	*
	* @param string $id
	*
	* @return string $id
	* @throws Exception
	*/
	protected function validateProviderID( $id )
	{
		foreach( $this->config["providers"] as $idpId => $config )
		{
			if( strtolower( $idpId ) == strtolower( $id ) )
			{
				$id = $idpId;
			}
		}

		if( ! isset( $this->config["providers"][$id] ) )
		{
			throw new Exception( "Unknown Provider ID.", 3 ); 
		}

		if( ! $this->config["providers"][$id]["enabled"] )
		{
			throw new Exception( "Provider disabled.", 3 );
		}

		return $id;
	}
}
