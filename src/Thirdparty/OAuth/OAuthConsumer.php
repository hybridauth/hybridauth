<?php
/*!
* This file is part of the OAuth PHP Library (https://code.google.com/p/oauth/)
*
* OAuth PHP Library is licensed under Apache License 2.0
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

