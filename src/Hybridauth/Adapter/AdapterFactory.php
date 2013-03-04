<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception;
use Hybridauth\Storage\StorageInterface;

class AdapterFactory
{
	protected $hybridauthConfig = null;
	protected $storage = null;

	// --------------------------------------------------------------------

	function __construct( $config, StorageInterface $storage = null )
	{
		$this->hybridauthConfig = $config;
		$this->storage          = $storage;
	}

	// --------------------------------------------------------------------

	/**
	* create a new adapter switch IDp name or ID
	*/
	function factory($id, $parameters = null)
	{
		// provider config
		$id     = $this->_getProviderCiId ( $id );
		$config = $this->_getConfigById ( $id );

		if ( ! $config ){
			throw new Exception( "Unknown Provider", Exception::UNKNOWN_OR_DISABLED_PROVIDER );
		}
		
		// check the IDp adapter is enabled
		if ( ! ( bool ) $config["enabled"] ){
			throw new Exception ( "Provider Disabled", Exception::UNKNOWN_OR_DISABLED_PROVIDER );
		}

		// adapter wrapper
		$providerClassName = "\\Hybridauth\\Provider\\" . $id . "\\" . $id . "Adapter";

		// definded wrapper?
		if ( isset( $config ["wrapper"] ) && $config["wrapper"] ) {
			if ( isset( $config["wrapper"]["path"] ) && $config["wrapper"]["path"] ){
				require_once $config["wrapper"]["path"];
			}

			$providerClassName = $config ["wrapper"] ["class"];
		}

		// create the adapter instance
		$providerInstance = new $providerClassName(
			$id, 
			$this->hybridauthConfig, 
			$config, 
			$parameters, 
			$this->storage
		);

		return $providerInstance;
	}

	// --------------------------------------------------------------------

	/**
	* Setup an adapter for a given provider
	*/
	function setup( $providerId, $parameters = array() )
	{
		if ( ! $parameters ){
			$parameters = $this->storage->get( $providerId . '.id_provider_params' );
		}

		return $this->factory( $providerId, $parameters );
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id
	*/
	private function _getConfigById( $id )
	{
		if ( isset( $this->hybridauthConfig['providers'][$id] ) ) {
			return $this->hybridauthConfig['providers'][$id];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id; insensitive
	*/
	private function _getProviderCiId( $id )
	{
		foreach( $this->hybridauthConfig['providers'] as $idpid => $params ){
			if( strtolower( $idpid ) == strtolower( $id ) ) {
				return $idpid;
			}
		}

		return null;
	}
}
