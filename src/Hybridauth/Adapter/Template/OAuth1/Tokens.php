<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template\OAuth1;

use Hybridauth\Adapter\Template\OAuth1\TokensInterface;

class Tokens implements TokensInterface
{
	function __construct( $accessToken = null, $accessSecretToken = null , $requestToken = null , $requestSecretToken = null )
	{
		$this->accessToken        = $accessToken;
		$this->accessSecretToken  = $accessSecretToken;
		$this->requestToken       = $requestToken;
		$this->requestSecretToken = $requestSecretToken;
	}
}
