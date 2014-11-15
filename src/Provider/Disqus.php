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
final class Disqus extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $scope = 'read, email';

	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://disqus.com/api/3.0/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://disqus.com/api/oauth/2.0/authorize';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://disqus.com/api/oauth/2.0/access_token/';

	/**
	* {@inheritdoc}
	*/
	protected function initialize() 
	{
		parent::initialize();
		
		$this->apiRequestParameters = array( 'api_key' => $this->clientId, 'api_secret' => $this->clientSecret );
	}

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'users/details' );

			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$data = $data->filter( 'response' );

		$userProfile->identifier  = $data->get( 'id' );
		$userProfile->displayName = $data->get( 'name' );
		$userProfile->description = $data->get( 'bio' );
		$userProfile->profileURL  = $data->get( 'profileUrl' );
		$userProfile->email       = $data->get( 'email' );
		$userProfile->region      = $data->get( 'location' );
		$userProfile->description = $data->get( 'about' );

		$userProfile->photoURL    = $data->filter( 'avatar' )->get( 'permalink' );

		$userProfile->displayName = $userProfile->displayName ? $userProfile->displayName : $data->get( 'username' );

		return $userProfile;
	}
}
