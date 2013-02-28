<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\OpenID;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;

/**
* OpenID adapter
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_OpenID.html
*/
class OpenIDAdapter extends AbstractAdapter implements AdapterInterface
{
	function initialize()
	{
		$this->registerAuthenticationService( '\Hybridauth\Provider\OpenID\Authentication' );
		
		$this->registerApiBinding( 'getUserProfile', '\Hybridauth\Provider\OpenID\Api\User' );
	}
}
