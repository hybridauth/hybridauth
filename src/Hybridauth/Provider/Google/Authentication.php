<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google;

use Hybridauth\Adapter\Authentication\OAuth2\Template;

class Authentication extends Template
{
	function initialize()
	{
		$this->letApplicationId( $this->getAdapterConfig( 'keys', 'id' ) );
		$this->letApplicationSecret( $this->getAdapterConfig( 'keys', 'secret' ) );
		
		$scope = $this->getAdapterConfig( 'scope' ) 
			? $this->getAdapterConfig( 'scope' ) 
			: 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.google.com/m8/feeds/';

		$this->letApplicationScope( $scope );

		$this->letEndpointBaseUri( '' );
		$this->letEndpointRedirectUri( $this->getHybridauthEndpointUri() );
		$this->letEndpointAuthorizeUri( 'https://accounts.google.com/o/oauth2/auth' );
		$this->letEndpointRequestTokenUri( 'https://accounts.google.com/o/oauth2/token' );
		$this->letEndpointTokenInfoUri( 'https://www.googleapis.com/oauth2/v1/tokeninfo' );

		$this->letEndpointAuthorizeUriAdditionalParameters( array( 'access_type' => 'offline' ) );

		parent::initialize();
	}
}
