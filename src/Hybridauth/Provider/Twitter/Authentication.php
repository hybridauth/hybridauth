<?php
/*
 * ! This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth) This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
 */
namespace Hybridauth\Provider\Twitter;

use Hybridauth\Adapter\Authentication\OAuth1\Template;

class Authentication extends Template {
	function initialize($options = array()) {
		$defaults = array (
				'api_base_uri' => 'https://api.twitter.com/1.1/',
				'authorize_uri' => 'https://api.twitter.com/oauth/authenticate',
				'request_token_uri' => 'https://api.twitter.com/oauth/request_token',
				'access_token_uri' => 'https://api.twitter.com/oauth/access_token' 
		);
		
		$options = array_merge ( $defaults, ( array ) $options );
		
		parent::initialize ( $options );
	}
}
