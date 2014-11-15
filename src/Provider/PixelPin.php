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
final class PixelPin extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://ws3.pixelpin.co.uk/index.php/api/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'https://login.pixelpin.co.uk/OAuth2/FLogin.aspx';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://ws3.pixelpin.co.uk/index.php/api/token';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenName = 'oauth_token';

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$response = $this->apiRequest( 'userdata' );
			
			$data = new Data\Collection( $response );
		}
		catch( Exception $e )
		{
			throw new Exception( 'User profile request failed! ' . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$userProfile->identifier    = $data->get( 'id' );
		$userProfile->firstName     = $data->get( 'firstName' );
		$userProfile->displayName   = $data->get( 'firstName' );
		$userProfile->email         = $data->get( 'email' );
        $userProfile->emailVerified = $data->get( 'email' );

		return $userProfile;
	}
}
