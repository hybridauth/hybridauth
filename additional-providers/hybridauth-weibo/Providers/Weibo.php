<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Weibo
 */
class Hybrid_Providers_Weibo extends Hybrid_Provider_Model_OAuth2
{
	// default permissions
	public $scope = "email";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.weibo.com/";
		$this->api->authorize_url = "https://api.weibo.com/oauth2/authorize";
		$this->api->token_url     = "https://api.weibo.com/oauth2/access_token";
        $this->token_info_url     = "https://api.weibo.com/oauth2/get_token_info";
	}

	private function authenticate( $code )
	{
		$params = array(
			"client_id"     => $this->api->client_id,
			"client_secret" => $this->api->client_secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->api->redirect_uri,
			"code"          => $code
		);

        $url = $this->api->token_url;
		$url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query( $params );

		$response = $this->api->api( $url, 'POST' );
		Hybrid_Logger::debug( "authenticate with url: $url" );

		if( ! $response || ! isset( $response->access_token ) ){
			throw new Exception( "The Authorization Service has return: " . $response->error );
		}

		if( isset( $response->access_token  ) )  $this->api->access_token           = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->api->refresh_token           = $response->refresh_token; 
		if( isset( $response->expires_in    ) ) $this->api->access_token_expires_in = $response->expires_in; 
		
		// calculate when the access token expire
		if( isset($response->expires_in)) {
			$this->api->access_token_expires_at = time() + $response->expires_in;
		}

		return $response;
	}

	/**
	* finish login step
    * copy from Auth.php, use customized authenticate function.
	*/
	function loginFinish()
	{
		$error = (array_key_exists('error',$_REQUEST))?$_REQUEST['error']:"";

		// check for errors
		if ( $error ){ 
			throw new Exception( "Authentication failed! {$this->providerId} returned an error: $error", 5 );
		}

		// try to authenicate user
		$code = (array_key_exists('code',$_REQUEST))?$_REQUEST['code']:"";

		try{
            // Use customized authenicate function.
			$this->authenticate( $code );
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		}

		// check if authenticated
		if ( ! $this->api->access_token ){ 
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		// store tokens
		$this->token( "access_token" , $this->api->access_token  );
		$this->token( "refresh_token", $this->api->refresh_token );
		$this->token( "expires_in"   , $this->api->access_token_expires_in );
		$this->token( "expires_at"   , $this->api->access_token_expires_at );

		// set user connected locally
		$this->setUserConnected();
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
        // Get uid
        $data = $this->api->api( "2/account/get_uid.json" );
        if ( ! isset( $data->uid ) ) {
            throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
        }
        $uid = $data->uid;

        $data = $this->api->api( "2/users/show.json", "GET", [ "uid" => $uid ] );
        if ( isset( $data->error_code ) ) {
            throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response: " . var_export($data, true), 6 );
        }

		$this->user->profile->identifier  = @ $data->id;
		$this->user->profile->displayName = @ $data->screen_name;
		$this->user->profile->description = @ $data->description;
		$this->user->profile->photoURL    = @ $data->profile_image_url;
		$this->user->profile->profileURL  = @ $data->url;
		$this->user->profile->region      = @ $data->location;

		return $this->user->profile;
	}
}
