<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template\OAuth1;

use Hybridauth\Adapter\Template\OAuth1\TokensInterface;

class Tokens implements TokensInterface {
	function __construct()
	{
		$this->accessToken        = null;
		$this->accessSecretToken  = null;
		$this->requestToken       = null;
		$this->requestSecretToken = null;
	}
}
