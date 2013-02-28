<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Authentication\OAuth1;

class Endpoints
{
	public $baseUri         = null;
	public $redirectUri     = null;
	public $authorizeUri    = null;
	public $requestTokenUri = null;

	public $authorizeUriParameters = array();
}
