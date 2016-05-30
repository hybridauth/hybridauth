<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_QQ
 */
class Hybrid_Providers_QQ extends Hybrid_Provider_Model_OAuth2
{ 
	// possible values: http://wiki.connect.qq.com/api%E5%88%97%E8%A1%A8
	// default permissions:
	public $scope = "get_user_info";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://graph.qq.com/";
		$this->api->authorize_url = "https://graph.qq.com/oauth2.0/authorize";
		$this->api->token_url     = "https://graph.qq.com/oauth2.0/token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
        // Preserve the deocde json option.
        $decode_json = $this->api->decode_json;

        // Get openid will not return json.
        $this->api->decode_json = false;

        // Get OpenID
        $response = $this->api->api( "oauth2.0/me" );
        $cnt = preg_match('/callback\((.*)\)/', $response, $matches);

        if ($cnt === 0) {
			throw new Exception( "User OpenID request failed! {$this->providerId} returned an invalid response.", 6 );
        }

        $data = json_decode($matches[1]);
        $openid = $data->openid;

        # Get User Info
        # This API returns json
        $this->api->decode_json = true;

        # oauth_consumer_key, openid
        $data = $this->api->api("user/get_user_info", "GET", [
            'oauth_consumer_key' => $this->api->client_id,
            'openid' => $openid
        ]);

		if ( $data->ret > 0 ) {
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response: " . var_export($data, true), 6 );
		}

		$this->user->profile->identifier  = $openid;
		$this->user->profile->displayName = @ $data->nickname;
		$this->user->profile->photoURL    = @ $data->figureurl;
		$this->user->profile->region      = $data->province . ' ' . $data->city;

        // Restore the decode json option
        $this->api->decode_json = $deocde_json;

		return $this->user->profile;
	}
}
