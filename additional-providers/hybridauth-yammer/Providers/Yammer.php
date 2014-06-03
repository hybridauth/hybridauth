<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Yammer OAuth Class
 *
 * @package             HybridAuth additional providers package
 * @author              Johnny Cao ( @l8cao ) at SoapBox Innovation Inc.
 * @version             1.0
 * @license             BSD License
 */

/**
 * Hybrid_Providers_Yammer adapter based on OAuth2 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Yammer.html
 */

class Hybrid_Providers_Yammer extends Hybrid_Provider_Model
{
	// default permissions
	// (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
	public $scope = "";

	/**
	* IDp wrappers initializer
	*/
	function initialize()
	{

		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
				throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		// override requested scope
		if( isset( $this->config["scope"] ) && ! empty( $this->config["scope"] ) ){
				$this->scope = $this->config["scope"];
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Yammer/Yammer.php";

		// create a new OAuth2 client instance
		$this->api = new YammerOAuth2Client( $this->config["keys"]["id"], $this->config["keys"]["secret"], $this->endpoint );

		// If we have an access token, set it
		if( $this->token( "access_token" ) ){
				$this->api->access_token            = $this->token( "access_token" );
				$this->api->refresh_token           = $this->token( "refresh_token" );
				$this->api->access_token_expires_in = $this->token( "expires_in" );
				$this->api->access_token_expires_at = $this->token( "expires_at" );
		}

		// Set curl proxy if exist
		if( isset( Hybrid_Auth::$config["proxy"] ) ){
				$this->api->curl_proxy = Hybrid_Auth::$config["proxy"];
		}

		// Provider api end-points
		$this->api->api_base_url  = "https://www.yammer.com/api/v1/";
		$this->api->authorize_url = "https://www.yammer.com/dialog/oauth";
		$this->api->token_url     = "https://www.yammer.com/oauth2/access_token.json";
		$this->api->token_info_url= "https://www.yammer.com/oauth2/access_token.json";
	}

	// --------------------------------------------------------------------

	/**
	* begin login step
	*/
	function loginBegin()
	{
		// redirect the user to the provider authentication url
		Hybrid_Auth::redirect( $this->api->authorizeUrl( array( "scope" => $this->scope ) ) );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step
	*/
	function loginFinish()
	{
		$error = (array_key_exists('error',$_REQUEST))?$_REQUEST['error']:"";

		// check for errors
		if ( $error ){
				throw new Exception( "Authentication failed! {$this->providerId} returned an error: $error", 5 );
		}

		// try to authenticate user
		$code = (array_key_exists('code',$_REQUEST))?$_REQUEST['code']:"";

		try{
				$this->api->authenticate( $code );
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
		// get data
		$data = $this->api->api( "https://www.yammer.com/api/v1/users/current.json" );

		// check for errors
		if ( ! isset( $data->id ) ){
				throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier        = @ $data->id;
		$this->user->profile->displayName        = @ $data->full_name ? $data->full_name : $data->first_name;
		$this->user->profile->firstName                = @ $data->first_name;
		$this->user->profile->lastName                = @ $data->last_name;
		$this->user->profile->photoURL                = @ $data->mugshot_url;
		$this->user->profile->profileURL        = @ $data->web_url;
		$this->user->profile->email                        = @ $data->contact->email_addresses[0]->address;
		$this->user->profile->state                        = @ $data->state;
		$this->user->profile->networkname        = @ $data->state;
		$this->user->profile->region                = @ $data->location;

		return $this->user->profile;
	}

	/**
	* load the subscribed Yammer users
	*
	*/
	function getUserContacts()
	{
		// Get user contacts
		$data = $this->api->api( "https://www.yammer.com/api/v1/subscriptions.json" );

		// check for errors
		if ( $this->api->http_code != 200 ){
				throw new Exception( "User contacts request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if( ! $data || ! count( $data->references ) ){
				return ARRAY();
		}

		$contacts = ARRAY();

		foreach( $data->references as $item ){
				$uc = new Hybrid_User_Contact();

				$uc->identifier                = @ $item->id;
				$uc->webSiteURL                = @ $item->web_url;
				$uc->profileURL                = @ $item->url;
				$uc->photoURL                = @ $item->mugshot_url;
				$uc->displayName        = @ $item->full_name ? $item->full_name : $item->name;
				$uc->activated_at        = @ $item->activated_at;
				$uc->job_title                = @ $item->job_title;
				$uc->state                        = @ $item->state;
				$uc->type                        = @ $item->type;
				$uc->stats                        = @ $item->stats;

				//no data for the following information
				//$uc->description =
				//$uc->email =
				$contacts[] = $uc;
		}

		return $contacts;
	}
}
