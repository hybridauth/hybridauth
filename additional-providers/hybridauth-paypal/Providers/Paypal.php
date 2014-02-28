<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/** 
 * PayPal OAuth2 Class
 * 
 * @package             HybridAuth providers package 
 * @author              Jan Waś <janek.jan@gmail.com>
 * @version             0.2
 * @license             BSD License
 */ 

/**
 * Hybrid_Providers_Paypal - PayPal provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Paypal extends Hybrid_Provider_Model_OAuth2
{
	// default permissions 
	public $scope = "profile email address phone https://uri.paypal.com/services/paypalattributes";

    public $sandbox = true;

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

		// include OAuth2 client and Paypal client
		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth2Client.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "Paypal/PaypalOAuth2Client.php";

		// create a new OAuth2 client instance
		$this->api = new PaypalOAuth2Client( $this->config["keys"]["id"], $this->config["keys"]["secret"], $this->endpoint );

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
        if ($this->sandbox) {
            $this->api->authorize_url  = "https://www.sandbox.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize";
            $this->api->token_url      = "https://api.sandbox.paypal.com/v1/oauth2/token";
            $this->api->token_info_url = "https://api.sandbox.paypal.com/v1/identity/openidconnect/tokenservice";
        } else {
            $this->api->authorize_url  = "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize";
            $this->api->token_url      = "https://api.paypal.com/v1/oauth2/token";
            $this->api->token_info_url = "https://api.paypal.com/v1/identity/openidconnect/tokenservice";
        }

        if (Hybrid_Auth::$config["debug_mode"]) {
            $this->api->curl_log = Hybrid_Auth::$config["debug_file"];
        }
	}

	/**
	* begin login step 
	*/
	/*function loginBegin()
	{
		$parameters = array("scope" => $this->scope, "grant_type" => "client_credentials");
		$optionals  = array("scope", "access_type", "redirect_uri", "approval_prompt", "hd");

		foreach ($optionals as $parameter){
			if( isset( $this->config[$parameter] ) && ! empty( $this->config[$parameter] ) ){
				$parameters[$parameter] = $this->config[$parameter];
			}
		}

		Hybrid_Auth::redirect( $this->api->authorizeUrl( $parameters ) ); 
    }*/

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed 
		$this->refreshToken();

		// ask google api for user infos
		$response = $this->api->api( "https://api".($this->sandbox?'.sandbox' : '').".paypal.com/v1/identity/openidconnect/userinfo/?schema=openid" ); 

		if ( ! isset( $response->payer_id ) || isset( $response->message ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier    = (property_exists($response,'payer_id'))?$response->payer_id:"";
		$this->user->profile->firstName     = (property_exists($response,'given_name'))?$response->given_name:"";
		$this->user->profile->lastName      = (property_exists($response,'family_name'))?$response->family_name:"";
		$this->user->profile->displayName   = (property_exists($response,'name'))?$response->name:"";
		$this->user->profile->photoURL      = (property_exists($response,'picture'))?$response->picture:"";
		$this->user->profile->gender        = (property_exists($response,'gender'))?$response->gender:""; 
		$this->user->profile->email         = (property_exists($response,'email'))?$response->email:"";
		$this->user->profile->emailVerified = (property_exists($response,'email_verified'))?$response->email_verified:"";
		$this->user->profile->language      = (property_exists($response,'locale'))?$response->locale:"";
		$this->user->profile->phone         = (property_exists($response,'phone_number'))?$response->phone_number:"";
        if (property_exists($response,'address')) {
            $address = $response->address;
            $this->user->profile->address   = (property_exists($address,'street_address'))?$address->street_address:"";
            $this->user->profile->city      = (property_exists($address,'locality'))?$address->locality:"";
            $this->user->profile->zip       = (property_exists($address,'postal_code'))?$address->postal_code:"";
            $this->user->profile->country   = (property_exists($address,'country'))?$address->country:"";
            $this->user->profile->region    = (property_exists($address,'region'))?$address->region:"";
        }

		if( property_exists($response,'birthdate') ){ 
            if (strpos($response->birthdate, '-') === false) {
                if ($response->birthdate !== '0000') {
                    $this->user->profile->birthYear  = (int) $response->birthdate;
                }
            } else {
                list($birthday_year, $birthday_month, $birthday_day) = explode( '-', $response->birthdate );

                $this->user->profile->birthDay   = (int) $birthday_day;
                $this->user->profile->birthMonth = (int) $birthday_month;
                if ($birthday_year !== '0000') {
                    $this->user->profile->birthYear  = (int) $birthday_year;
                }
            }
		}

		return $this->user->profile;
	}
}
