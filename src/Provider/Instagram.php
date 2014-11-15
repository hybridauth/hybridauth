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
 * By Sebastian Lasse - https://github.com/sebilasse
 */
final class Instagram extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $scope = 'basic'; 

	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://api.instagram.com/v1/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://api.instagram.com/oauth/authorize/';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://api.instagram.com/oauth/access_token';

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'users/self/' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$data = $data->filter( 'id' );

		$userProfile->identifier  = $data->get( 'id' );
		$userProfile->description = $data->get( 'bio' );
		$userProfile->photoURL    = $data->get( 'profile_picture' );
		$userProfile->webSiteURL  = $data->get( 'website' );
		$userProfile->displayName = $data->get( 'full_name' ); 

		$userProfile->displayName = $userProfile->displayName ? $userProfile->displayName : $data->get( 'username' );

		return $userProfile;
	}
}
