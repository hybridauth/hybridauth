<?php 
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_Steam provider adapter based on OpenID protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Steam.html
 */
class Hybrid_Providers_Steam extends Hybrid_Provider_Model_OpenID
{
	var $openidIdentifier = "http://steamcommunity.com/openid";

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		parent::loginFinish();

		$uid = str_replace( "http://steamcommunity.com/openid/id/", "", $this->user->profile->identifier );

		if( $uid ){
			$data = @ file_get_contents( "http://steamcommunity.com/profiles/$uid/?xml=1" ); 

			$data = @ new SimpleXMLElement( $data );

			if ( ! is_object( $data ) ){
				return false;
			}

			$this->user->profile->displayName  = (string) $data->{'steamID'};
			$this->user->profile->photoURL     = (string) $data->{'avatarMedium'};
			$this->user->profile->description  = (string) $data->{'summary'};
			
			$realname = (string) $data->{'realname'}; 

			if( $realname ){
				$this->user->profile->firstName = $realname;
			}
			
			$customURL = (string) $data->{'customURL'};

			if( $customURL ){
				$this->user->profile->profileURL = "http://steamcommunity.com/id/$customURL/";
			}

			// restore the user profile
			Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );
		}
	}
}
