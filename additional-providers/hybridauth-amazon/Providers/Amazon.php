<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Amazon provider adapter based on OAuth2 protocol
 *
 * added by skyverge | https://github.com/skyverge
 *
 * The Provider is very similar to standard Oauth2 providers with a few differences:
 * - it sets the Content-Type header explicitly to application/x-www-form-urlencoded
 *   as required by Amazon
 * - it uses a custom OAuth2Client, because the built-in one does not use http_build_query()
 *   to set curl POST params, which causes cURL to set the Content-Type to multipart/form-data
 *
 * @property OAuth2Client $api
 */
class Hybrid_Providers_Amazon extends Hybrid_Provider_Model_OAuth2 {

	// default permissions
	public $scope = 'profile postal_code';

	/**
	 * IDp wrappers initializer
	 */
	function initialize() {

		if ( ! $this->config['keys']['id'] || ! $this->config['keys']['secret'] ) {
			throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		// override requested scope
		if ( isset( $this->config['scope'] ) && ! empty( $this->config['scope'] ) ) {
			$this->scope = $this->config['scope'];
		}

		// include OAuth2 client
		require_once Hybrid_Auth::$config['path_libraries'] . 'OAuth/OAuth2Client.php';
		require_once Hybrid_Auth::$config['path_libraries'] . 'Amazon/AmazonOAuth2Client.php';

		// create a new OAuth2 client instance
		$this->api = new AmazonOAuth2Client( $this->config['keys']['id'], $this->config['keys']['secret'], $this->endpoint, $this->compressed );

		$this->api->api_base_url  = 'https://api.amazon.com';
		$this->api->authorize_url = 'https://www.amazon.com/ap/oa';
		$this->api->token_url     = 'https://api.amazon.com/auth/o2/token';

		$this->api->curl_header   = array( 'Content-Type: application/x-www-form-urlencoded' );

		// If we have an access token, set it
		if ( $this->token( 'access_token' ) ) {
			$this->api->access_token            = $this->token('access_token');
			$this->api->refresh_token           = $this->token('refresh_token');
			$this->api->access_token_expires_in = $this->token('expires_in');
			$this->api->access_token_expires_at = $this->token('expires_at');
		}

		// Set curl proxy if exists
		if ( isset( Hybrid_Auth::$config['proxy'] ) ) {
			$this->api->curl_proxy = Hybrid_Auth::$config['proxy'];
		}
	}

	/**
	 * load the user profile from the IDp api client
	 */
	function getUserProfile() {

		$data = $this->api->get( '/user/profile' );

		if ( ! isset( $data->user_id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->user_id;
		$this->user->profile->email       = @ $data->email;
		$this->user->profile->displayName = @ $data->name;
		$this->user->profile->zip         = @ $data->postal_code;

		return $this->user->profile;
	}
}
