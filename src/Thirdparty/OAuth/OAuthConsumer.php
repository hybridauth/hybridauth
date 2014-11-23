<?php
/*!
* This file is part of the OAuth PHP Library (https://code.google.com/p/oauth/)
*
* OAuth PHP Library is an open source software available under the MIT License.
*/

namespace Hybridauth\Thirdparty\OAuth;

class OAuthConsumer
{
	public $key;
	public $secret;

	function __construct($key, $secret, $callback_url = NULL)
	{
		$this->key = $key;
		$this->secret = $secret;
		$this->callback_url = $callback_url;
	}

	function __toString()
	{
		return "OAuthConsumer[key=$this->key,secret=$this->secret]";
	}
}
