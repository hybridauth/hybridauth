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
class Vkontakte  extends OAuth2
{
	/**
	* {@inheritdoc}
	*/
	protected $apiBaseUrl = 'https://api.vk.com/method/';

	/**
	* {@inheritdoc}
	*/
	protected $authorizeUrl = 'http://api.vk.com/oauth/authorize';

	/**
	* {@inheritdoc}
	*/
	protected $accessTokenUrl = 'https://api.vk.com/oauth/token';

	/**
	* Need to store user_id as token for later use 
	*
	* {@inheritdoc}
	*/
	protected function validateAccessTokenExchange( $response )
	{
		$data = parent::validateAccessTokenExchange( $response );

		$this->token( 'user_id', $data->get( 'user_id') );
	}

	/**
	* {@inheritdoc}
	*/
	function getUserProfile()
	{
		try
		{
			$parameters = [
				'uid'    => $this->token( 'user_id' ),
				'fields' => 'first_name,last_name,nickname,screen_name,sex,bdate,timezone,photo_rec,photo_big'
			];

			$response = $this->apiRequest( 'users.getInfo', 'GET', $parameters );

			$data = new Data\Collection( $response->response[0] );
		}
		catch( Exception $e )
		{
			throw new Exception( "User profile request failed! " . $e->getMessage(), 6 );
		}

		$userProfile = new User\Profile();

		$userProfile->identifier  = $data->get( 'uid' );
		$userProfile->firstName   = $data->get( 'first_name' );
		$userProfile->lastName    = $data->get( 'last_name' );
		$userProfile->displayName = $data->get( 'screen_name' );
		$userProfile->photoURL    = $data->get( 'photo_big' );

		$userProfile->profileURL  = $data->get( 'screen_name' ) ? 'http://vk.com/' . $data->get( 'screen_name' ) : '';

		if( $data->exists( 'sex' ) )
		{
			switch( $data->get( 'sex' ) )
			{
				case 1: $userProfile->gender = 'female'; break;
				case 2: $userProfile->gender = 'male'; break;
			}
		}

		return $userProfile;
	}
}

