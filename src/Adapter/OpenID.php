<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\Data;
use Hybridauth\HttpCLient;
use Hybridauth\Exception;
use Hybridauth\User;

use Hybridauth\Thirdparty\OpenID\LightOpenID;

/**
 *
 */
class OpenID extends AdapterBase implements AdapterInterface 
{
	/**
	* LightOpenID instance
	*
	* @var object
	*/
	protected $openIdClient = null;

	/**
	* Openid provider identifier
	*
	* @var string
	*/
	protected $openidIdentifier = '';

	/**
	/**
	* Adapter initializer
	*
	* @throws Exception
	*/
	protected function initialize()
	{
		if( $this->config->exists( 'openid_identifier' ) )
		{
			$this->openidIdentifier = $this->config->get( 'openid_identifier' );
		}

		if( $this->params->exists( 'openid_identifier' ) )
		{
			$this->openidIdentifier = $this->params->get( 'openid_identifier' );
		}

		if( empty( $this->openidIdentifier ) )
		{
			throw new Exception( 'OpenID adapter requires an openid_identifier.', 4 );
		}

		$hostPort = parse_url( $this->endpoint, PHP_URL_PORT );
		$hostUrl  = parse_url( $this->endpoint, PHP_URL_HOST );

		if( $hostPort )
		{
			$hostUrl .= ':' . $hostPort;
		}

		$this->openIdClient = new LightOpenID( $hostUrl, null );
	}

	/**
	*
	*/
	function authenticate()
	{
		if( $this->isAuthorized() )
		{
			return true;
		}

		if( ! isset( $_GET['openid_mode'] ) )
		{
			$this->authenticateBegin();
		}

		else
		{
			$this->authenticateFinish();
		}
	}

	/**
	*
	*/
	function isAuthorized()
	{
		return (bool) $this->storage->get( $this->providerId . '.user' );
	}

	/**
	*
	*/
	function disconnect()
	{
		$this->clearTokens();

		return true;
	}

	/**
	* Initiate the authorization protocol
	*
	* Include and instantiate LightOpenID
	*/
	function authenticateBegin()
	{
		$this->openIdClient->identity  = $this->openidIdentifier;
		$this->openIdClient->returnUrl = $this->endpoint;
		$this->openIdClient->required  = array(
			'namePerson/first'       ,
			'namePerson/last'        ,
			'namePerson/friendly'    ,
			'namePerson'             ,

			'contact/email'          ,

			'birthDate'              ,
			'birthDate/birthDay'     ,
			'birthDate/birthMonth'   ,
			'birthDate/birthYear'    ,

			'person/gender'          ,
			'pref/language'          ,

			'contact/postalCode/home',
			'contact/city/home'      ,
			'contact/country/home'   ,

			'media/image/default'    ,
		);

		HttpClient\Util::redirect( $this->openIdClient->authUrl() );
	}

	/**
	* Validate and fetch the user profile.
	*/
	function authenticateFinish()
	{
		if( $this->openIdClient->mode == 'cancel' )
		{
			throw new Exception( 'Authentication failed! User has cancelled authentication!', 5 );
		}

		if( ! $this->openIdClient->validate() )
		{
			throw new Exception( 'Authentication failed. Invalid request received!', 5 );
		}

		$response = $this->openIdClient->getAttributes();

		$collection = new Data\Collection( $response );

		$userProfile = new User\Profile();

		$userProfile->identifier  = $this->openIdClient->identity;

		$userProfile->firstName   = $collection->get( 'namePerson/first' );
		$userProfile->lastName    = $collection->get( 'namePerson/last' );
		$userProfile->displayName = $collection->get( 'namePerson' );
		$userProfile->email       = $collection->get( 'contact/email' );
		$userProfile->language    = $collection->get( 'pref/language' );
		$userProfile->country     = $collection->get( 'contact/country/home' );
		$userProfile->zip         = $collection->get( 'contact/postalCode/home' );
		$userProfile->gender      = $collection->get( 'person/gender' );
		$userProfile->photoURL    = $collection->get( 'media/image/default' );
		$userProfile->birthDay    = $collection->get( 'birthDate/birthDay' );
		$userProfile->birthMonth  = $collection->get( 'birthDate/birthMonth' );
		$userProfile->birthYear   = $collection->get( 'birthDate/birthDate' );

		$userProfile->displayName = $userProfile->displayName ? $userProfile->displayName : $collection->get( 'namePerson/friendly' );
		$userProfile->displayName = $userProfile->displayName ? $userProfile->displayName : trim( $userProfile->firstName . ' ' . $userProfile->lastName );

		$userProfile = $this->fetchUserGender( $userProfile, $collection->get( 'person/gender' ) );

		// with openid providers we get the user profile only once, so store it
		$this->storage->set( $this->providerId . '.user', $userProfile );
	}

	/**
	*
	*/
	protected function fetchUserGender( $userProfile, $gender )
	{
		if( 'f' == strtolower( $gender ) )
		{
			$gender = 'female';
		}

		if( 'm' == strtolower( $gender ) )
		{
			$gender = 'male';
		}

		$userProfile->gender = $gender;

		return $userProfile;
 	}

	/**
	* OpenID only provide the user profile one. This method will attempt to retrieve the profile from storage.
	*/
	function getUserProfile()
	{
		$userProfile = $this->storage->get( $this->providerId . '.user' );

		if( ! is_object( $userProfile ) )
		{
			throw new Exception( "User profile request failed! User is not connected to {$this->providerId} or his session has expired.", 6 );
		}

		return $userProfile;
	}
}
