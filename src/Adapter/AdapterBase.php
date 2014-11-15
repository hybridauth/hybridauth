<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/
namespace Hybridauth\Adapter;

use Hybridauth\Error;
use Hybridauth\Logger;
use Hybridauth\Exception;
use Hybridauth\Data;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Storage\Session;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Curl;

/**
 *
 */
abstract class AdapterBase implements AdapterInterface 
{
	use AdapterTokensTrait, HelperTrait;

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
	protected $config = array();

	/**
	* Extra Provider parameters
	*
	* @var mixed
	*/
	protected $params = array();

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
	* @param string $providerId
	* @param array  $config
	* @param array  $params
	* @param object $httpClient
	* @param object $storage
	* @param object $logger
	*/
	function __construct( $config = array(), $params = array(), $httpClient = null, $storage = null, $logger = null )
	{
		$this->providerId = str_replace( 'Hybridauth\\Provider\\', '', get_class($this) );
		
		$this->httpClient = $httpClient ? $httpClient : new Curl();
		$this->storage    = $storage ? $storage : new Session();
		$this->logger     = $logger  ? $storage : new Error( $this->storage );

		$this->config = $config;
		$this->params = $params ? $params : $this->storage->get( $this->providerId . '.id_provider_params' );

		$this->config = new Data\Collection( $this->config );
		$this->params = new Data\Collection( $this->params );

		$this->endpoint   = $this->config->exists( 'callback' ) ? $this->config->get( 'callback' ) : $this->storage->get( $this->providerId . '.hauth_endpoint' );

		$this->initialize();
	}

	/**
	* Adapter initializer
	*/
	abstract protected function initialize(); 

	/**
	* {@inheritdoc}
	*/
	function getUserContacts()
	{
		throw new Exception( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* {@inheritdoc}
	*/
	function setUserStatus( $status )
	{
		throw new Exception( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* {@inheritdoc}
	*/
	function getUserActivity( $stream )
	{
		throw new Exception( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* Return oauth access tokens
	*
	* @param array $tokensNames
	*
	* @return array
	*/
	function getAccessToken( $tokenNames = array() )
	{
		throw new Exception( 'Provider does not support this feature.', 8 ); 
	}

	/**
	* Reset adapter access tokens
	*
	* @param array $tokens
	*/
	function setAccessToken( $tokens = array() )
	{
		$this->setTokens( $tokens );
	}
}
