<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Authentication\OAuth1;

class Tokens implements \Hybridauth\Adapter\Authentication\OAuth1\TokensInterface {
	function __construct() {
		$this->accessToken = null;
		$this->accessSecretToken = null;
		$this->requestToken = null;
		$this->requestSecretToken = null;
	}
}
