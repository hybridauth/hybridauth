<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception;
use Hybridauth\Data;
use Hybridauth\User;

/**
 *
 */
class Foursquare extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://api.foursquare.com/v2/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://foursquare.com/oauth2/authenticate';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://foursquare.com/oauth2/access_token';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenName = 'oauth_token';

	/**
	* {@inheritdoc}
	*/
	protected function initialize()
	{
		parent::initialize();
		
		$apiVersion = $this->config->exists( 'api_version' ) ? $this->config->get( 'api_version' ) : '20120610';

		$this->apiRequestParameters = array( "v" => $apiVersion );
	}

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'users/self' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$data = $data->filter( 'response' )->filter( 'user' );

		$userProfile->identifier    = $data->get( 'id' );
		$userProfile->firstName     = $data->get( 'firstName' );
		$userProfile->lastName      = $data->get( 'lastName' ); 
		$userProfile->gender        = $data->get( 'gender' );
		$userProfile->city          = $data->get( 'homeCity' ); 

		$userProfile->email         = $data->filter( 'contact' )->get( 'email' ); 
		$userProfile->emailVerified = $userProfile->email;

		$userProfile->profileURL    = 'https://www.foursquare.com/user/' . $userProfile->identifier; 
		$userProfile->displayName   = trim( $userProfile->firstName . ' ' . $userProfile->lastName );

		if( $data->exists( 'photo' ) )
		{
			$userProfile->photoURL = $data->filter( 'photo' )->get( 'prefix' ) . '150x150' . $data->filter( 'photo' )->get( 'suffix' );
		}

		return $userProfile;
	}
}
