<?php
/**
 * AOL OpenAuth PHP Client
 * 
 * A basic class to work with AOL's OpenAuth protocol.
 *
 * @author		Zachy <hybridauth@gmail.com>
 * @license		MIT License 
 * @link		http://dev.aol.com/api/openauth
 */ 
class AOL_OpenAuth_Client
{
	var $devid      = NULL;
	var $callback   = NULL;
	var $authToken  = NULL;

	var $aolApiUrls = array
		( 
			"login"   =>  "http://api.screenname.aol.com/auth/login?f=qs&r=27&devId={DEV_ID}&succUrl={CALLBACK_URL}",
			"profile" =>  "http://api.screenname.aol.com/auth/getInfo?f=json&devId={DEV_ID}&a={AUTH_TOKEN}&referer={CALLBACK_URL}",
			"logout"  =>  "http://api.screenname.aol.com/auth/logout?f=qs&devId={DEV_ID}&a={AUTH_TOKEN}&succUrl={CALLBACK_URL}" 
		);

	var $aolApiErr  = array
		(
		    200 => "Success (Ok)",
			330 => "More authentication required",
			340 => "More rights required",
			400 => "Invalid request",
			401 => "Unauthorized (authentication required)",
			405 => "Method not allowed",
			408 => "Request timeout",
			430 => "Source rate limit reached",
			440 => "Invalid Key",
			441 => "Key usage limit reached",
			442 => "Key invalid IP",
			443 => "Key used from unauthorized site",
			444 => "token used from unauthorized site (Referer doesn't match the value in token)",
			450 => "Rights denied",
			451 => "Permission denied",
			460 => "Missing required parameter",
			462 => "Parameter error",
			500 => "Generic Server Error"
		);

   /**
	* client constructor, require your aol devid
	*/
	function __construct( $devid, $callback = NULL ) {
		if( empty( $devid ) )
		{
			throw new Exception( "Parameter missing! you have to set your AOL DEV_ID.", 440 );
		}

		$this->devid     = $devid;
		$this->callback  = $callback ;  
	}

   /**
	* Try to authenticates the user 
	* 
	* Note: Authenticates the user (via Secure login form if not already authenticated) and returns an AOL
	* Authentication Token in a browser. The "login" method is only supported as a browser redirect api. 
	*/
	function require_login()
	{
		$this->expire_session();
	
		$login_url = $this->aolApiUrls["login"];
		$login_url = str_replace("{DEV_ID}", $this->devid, $login_url);
		$login_url = str_replace("{CALLBACK_URL}", urlencode( $this->callback ), $login_url);
 
		$_SESSION["_REFERER_SUCC_URL_"] = $this->callback;
 
	    header( "Location: " . $login_url );

		exit(0);
	}

   /**
	* return current logged in user AOL ID
	*/
	function get_loggedin_user()
	{ 
		if( isset( $_REQUEST["statusCode"] ) && $_REQUEST["statusCode"] != "200" )
		{
			throw new Exception( "AOL! OpenAuth authentication Failed. Possible error: " .  
									$this->aolApiUrls[ $_REQUEST["statusCode"] ], 
									$_REQUEST["statusCode"] ); 
		}

		if( isset( $_REQUEST["token_a"] ) )
		{
			$_SESSION["_AOL_CLIENT_AUTH_TOKEN_"] = $_REQUEST["token_a"];

			return $this->authToken = $_SESSION["_AOL_CLIENT_AUTH_TOKEN_"]; 
		}

		if( isset( $_SESSION["_AOL_CLIENT_AUTH_TOKEN_"] ) ) 
		{
			return $this->authToken = $_SESSION["_AOL_CLIENT_AUTH_TOKEN_"]; 
		}

		return NULL;
	}

   /**
	* return user profile
	*/
	function get_loggedin_user_infos()
	{
		$info_url = $this->aolApiUrls["profile"];
		$info_url = str_replace("{DEV_ID}", $this->devid, $info_url);
		$info_url = str_replace("{AUTH_TOKEN}", urlencode( $_SESSION["_AOL_CLIENT_AUTH_TOKEN_"] ), $info_url);
		$info_url = str_replace("{CALLBACK_URL}", $_SESSION["_REFERER_SUCC_URL_"], $info_url);
// print_r( $info_url );
	    // Send request using curl
	    $getInfoRequest = curl_init();
	    curl_setopt($getInfoRequest, CURLOPT_URL, $info_url );
	    curl_setopt($getInfoRequest, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($getInfoRequest);
// print_r( $response );
	    if ( curl_errno( $getInfoRequest ) )
		{
			return NULL; 
	    }

	    curl_close( $getInfoRequest );
		
		return $response; 
 
// print_r( json_decode( $response ) );
		// $json = json_decode( $response );

		// if ( $json )
		// { 
			// return $json->response->data->userData; 
			
		// }
	}

   /**
	* expire current logged in user, without logout
	*/
	function expire_session()
	{ 
		unset( $_SESSION["_AOL_CLIENT_AUTH_TOKEN_"] );
		
		unset( $_SESSION["_REFERER_SUCC_URL_"] ); 
	}

   /**
	* return aol auth token
	*/
	function get_auth_token()
	{
		if( ! isset( $_SESSION["_AOL_CLIENT_AUTH_TOKEN_"] ) ){
			return NULL;
		}

		return $_SESSION["_AOL_CLIENT_AUTH_TOKEN_"];
	}

   /**
	* set aol auth token
	*/
	function set_auth_token( $token )
	{
		$_SESSION["_AOL_CLIENT_AUTH_TOKEN_"] = $token;
	}
}
