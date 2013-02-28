<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google;

use Hybridauth\Adapter\Authentication\OAuth2\Template;

class Authentication extends Template
{
	function initialize( $options = array() )
	{
		$defaults = array(
			'scope' => 'https://www.googleapis.com/auth/userinfo.profile ' 
					. 'https://www.googleapis.com/auth/userinfo.email '
					. 'https://www.google.com/m8/feeds/',

			'authorize_uri'     => 'https://accounts.google.com/o/oauth2/auth',
			'request_token_uri' => 'https://accounts.google.com/o/oauth2/token',
			'token_info_uri'    => 'https://www.googleapis.com/oauth2/v1/tokeninfo',

			'authorize_uri_args' => array( 'access_type' => 'offline' ),
		);

		$options = array_merge( $defaults, (array) $options );

		parent::initialize( $options );
	}
}
