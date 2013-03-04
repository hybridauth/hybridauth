<?php
/*
 * ! This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth) This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
 */
namespace Hybridauth\Provider\Twitter;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;

/**
 * Twitter adapter
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Twitter.html
 */
class TwitterAdapter extends AbstractAdapter implements AdapterInterface {
	function initialize() {
		$this->registerAuthenticationService ( '\Hybridauth\Provider\Twitter\Authentication' );
		
		$this->registerApiBinding ( 'getUserProfile', '\Hybridauth\Provider\Twitter\Api\User' );
	}
}
