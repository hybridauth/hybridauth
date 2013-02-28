<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Facebook;

use Hybridauth\Adapter\Authentication\OAuth2\Template;

class Authentication extends Template
{
	function initialize( $options = array() )
	{
		$defaults = array(
			'scope' => 'email,user_about_me,user_birthday,user_hometown,user_website,read_stream,offline_access,publish_stream,read_friendlists',

			'authorize_uri'     => 'https://www.facebook.com/dialog/oauth',
			'request_token_uri' => 'https://graph.facebook.com/oauth/access_token',

			'authorize_uri_args' => array( 'display' => 'page' ),
		);

		$options = array_merge( $defaults, (array) $options );

		parent::initialize( $options );
	}
}
