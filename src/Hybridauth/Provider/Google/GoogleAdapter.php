<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;

/**
* Google adapter
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
*/
class GoogleAdapter extends AbstractAdapter implements AdapterInterface
{
	function initialize()
	{
		$this->registerAuthenticationService( '\Hybridauth\Provider\Google\Authentication' );

		$this->registerApiBinding( 'getUserProfile', '\Hybridauth\Provider\Google\Api\User' );
		$this->registerApiBinding( 'getUserContacts', '\Hybridauth\Provider\Google\Api\Contacts' );
	}
}
