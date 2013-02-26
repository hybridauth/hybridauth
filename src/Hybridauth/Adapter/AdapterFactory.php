<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

class AdapterFactory
{
	protected $hybridauthConfig = null; 
	protected $storage          = null;

	// --------------------------------------------------------------------

	function __construct( $config, \Hybridauth\Storage\StorageInterface $storage = null )
	{
		$this->hybridauthConfig = $config;
		$this->storage = $storage;
	}

	// --------------------------------------------------------------------

	/**
	* create a new adapter switch IDp name or ID
	*
	* @param string  $id      The id or name of the IDp
	* @param array   $params  (optional) required parameters by the adapter 
	*/
	function factory( $id, $providerParameters = null )
	{
		# init the adapter config and params
		$providerParameters = $providerParameters;
		$providerConfig     = $this->_getConfigById( $id );
		$id                 = $this->_getProviderCiId( $id );

		# check the IDp config
		if( ! $providerConfig ){
			throw new
				\Hybridauth\Exception( "Unknown Provider", \Hybridauth\Exception::UNKNOWN_OR_DISABLED_PROVIDER );
		}

		# check the IDp adapter is enabled
		if( ! (bool) $providerConfig["enabled"] ){
			throw new
				\Hybridauth\Exception( "Provider Disabled",  \Hybridauth\Exception::UNKNOWN_OR_DISABLED_PROVIDER );
		}

		# include the adapter wrapper
		$providerClassName = "\\Hybridauth\\Provider\\" . $id . "\\Adapter";

		if( isset( $providerConfig["wrapper"] ) && $providerConfig["wrapper"] ){
			if( isset( $providerConfig["wrapper"]["path"] ) && $providerConfig["wrapper"]["path"] ){
				require_once $providerConfig["wrapper"]["path"];
			}

			$providerClassName = $providerConfig["wrapper"]["class"];
		}

		# create the adapter instance, and pass the current params and config
		$providerInstance = new $providerClassName(
								$id,
								$this->hybridauthConfig,
								$providerConfig,
								$providerParameters ,
								$this->storage
							);

		return $providerInstance;
	}

	// --------------------------------------------------------------------

	/**
	* Setup an adapter for a given provider
	*/
	public function setup( $providerId, $providerParameters = array() )
	{
		if( ! $providerParameters ){
			$providerParameters = $this->storage->get( "hauth_session.$providerId.id_provider_params" );
		}

		return $this->factory( $providerId, $providerParameters );
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id
	*/
	private function _getConfigById( $id )
	{ 
		if( isset( $this->hybridauthConfig["providers"][$id] ) ){
			return $this->hybridauthConfig["providers"][$id];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id; insensitive
	*/
	private function _getProviderCiId( $id )
	{
		foreach( $this->hybridauthConfig["providers"] as $idpid => $params ){
			if( strtolower( $idpid ) == strtolower( $id ) ){
				return $idpid;
			}
		}

		return null;
	}
}
