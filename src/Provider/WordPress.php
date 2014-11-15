<?php
/**
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
final class WordPress  extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://public-api.wordpress.com/rest/v1/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://public-api.wordpress.com/oauth2/authenticate';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://public-api.wordpress.com/oauth2/token';

	/**
	* {@inheritdoc}
	*/
	protected function initialize() 
	{
		parent::initialize();

		$this->apiRequestHeaders = array( 'Authorization' => 'Bearer ' . $this->token( 'access_token' ) );
	}

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'me/' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$userProfile->identifier  = $data->get( 'ID' );
		$userProfile->displayName = $data->get( 'display_name' );
		$userProfile->photoURL    = $data->get( 'avatar_URL' );
		$userProfile->profileURL  = $data->get( 'profile_URL' );
		$userProfile->email       = $data->get( 'email' );
		$userProfile->language    = $data->get( 'language' );

		$userProfile->displayName   = $userProfile->displayName ? $userProfile->displayName : $data->get( 'username' );
		$userProfile->emailVerified = ( 1 == $data->get( 'email_verified' ) ) ? $data->get( 'email' ) : '';

		return $userProfile;
	}
}
