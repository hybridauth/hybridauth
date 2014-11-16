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
use Hybridauth\HttpClient;
use Hybridauth\HttpClient\HttpClientInterface;
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
	protected $version = "3.0.0";

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
	*
	*/
	function __construct( $config = array(), HttpClientInterface $httpClient = null, StorageInterface $storage = null )
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

		$this->logger = new Logger( 
			( isset( $config['debug_mode'] ) ? $config['debug_mode'] : false ),
			( isset( $config['debug_file'] ) ? $config['debug_file'] : '' ) 
		);

		$this->storage = $storage ? $storage : new Storage\Session();

		$this->httpClient = $httpClient ? $httpClient : new HttpClient\Curl();

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
	* 
	*/
	function getHttpClient()
	{
		return $this->httpClient;
	}

	/**
	* Get provider config by ID
	*
	* @param $id
	*/
	protected function getProviderConfigById( $id )
	{
		$id = $this->validateProviderID( $id );

		$config = [];

		if( isset( $this->config['callback'] ) )
		{
			$config['callback'] = $this->config['callback'];
		}

		// alias, for backward compatibility sake
		if( isset( $this->config['base_url'] ) )
		{
			$config['callback'] = $this->config['base_url'];
		}

		if( isset( $this->config['providers'][$id]['callback'] ) )
		{
			$config['callback'] = $this->config['providers'][$id]['callback'];
		}

		if( isset( $this->config['providers'][$id]['keys']['id'] ) )
		{
			$config['keys']['id'] = $this->config['providers'][$id]['keys']['id'];
		}

		if( isset( $this->config['providers'][$id]['keys']['key'] ) )
		{
			$config['keys']['key'] = $this->config['providers'][$id]['keys']['key'];
		}

		if( isset( $this->config['providers'][$id]['keys']['secret'] ) )
		{
			$config['keys']['secret'] = $this->config['providers'][$id]['keys']['secret'];
		}

		if( isset( $this->config['providers'][$id]['endpoints'] ) )
		{
			$config['endpoints'] = $this->config['providers'][$id]['endpoints'];
		}

		return $config;
	}

	/**
	* Get provider real provider ID. (case sensitive)
	*
	* @param $id
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
