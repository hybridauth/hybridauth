<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\OpenID\Api;

use Hybridauth\Exception;
use Hybridauth\Adapter\Api\AbstractApiOperations;

class User extends AbstractApiOperations
{
	function getUserProfile()
	{
		return $this->getAuthService()->storage->get( $this->getAuthService()->providerId . ".user" );
	}
}
