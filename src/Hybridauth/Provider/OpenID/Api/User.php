<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\OpenID\Api;

class User
{
	function getUserProfile( $options = array() )
	{
		return $this->api->storage->get( "hauth_session.{$this->api->providerId}.user" );
	}
}
