<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Facebook;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;

/**
* Facebook adapter
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html
*/
class FacebookAdapter extends AbstractAdapter implements AdapterInterface
{
	function initialize()
	{
		$this->registerAuthenticationService ( '\Hybridauth\Provider\Facebook\Authentication' );

		$this->registerApiBinding ( 'getUserProfile', '\Hybridauth\Provider\Facebook\Api\User' );
		$this->registerApiBinding ( 'getUserContacts', '\Hybridauth\Provider\Facebook\Api\Contacts' );

		// maybe... maybe not
			// $this->registerApiBinding( 'getUserFeed', '\Hybridauth\Provider\Facebook\Api\Feed' );
			// $this->registerApiBinding( 'getUserAlbums', '\Hybridauth\Provider\Facebook\Api\Media' );
			// $this->registerApiBinding( 'getUserPhotos', '\Hybridauth\Provider\Facebook\Api\Media' );
			// $this->registerApiBinding( 'uploadPhoto', '\Hybridauth\Provider\Facebook\Api\Media' );
			// $this->registerApiBinding( 'uploadVideo', '\Hybridauth\Provider\Facebook\Api\Media' );
			// $this->registerApiBinding( 'fql', '\Hybridauth\Provider\Facebook\Api\Fql' );
	}
}
