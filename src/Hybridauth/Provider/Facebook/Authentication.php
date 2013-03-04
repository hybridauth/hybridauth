<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Facebook;

use Hybridauth\Adapter\Authentication\OAuth2\Template;

class Authentication extends Template
{
	function initialize()
	{
		$this->letApplicationId( $this->getAdapterConfig( 'keys', 'id' ) );
		$this->letApplicationSecret( $this->getAdapterConfig( 'keys', 'secret' ) );

		$scope = $this->getAdapterConfig( 'scope' ) 
			? $this->getAdapterConfig( 'scope' ) 
			: 'email,user_about_me,user_birthday,user_hometown,user_website,read_stream,offline_access,publish_stream,read_friendlists';

		$this->letApplicationScope( $scope );

		$this->letEndpointRedirectUri( $this->getHybridauthEndpointUri() );
		$this->letEndpointAuthorizeUri( 'https://www.facebook.com/dialog/oauth' );
		$this->letEndpointRequestTokenUri( 'https://graph.facebook.com/oauth/access_token' );

		$this->letEndpointAuthorizeUriAdditionalParameters( array( 'display' => 'page' ) );

		parent::initialize();
	}
}
