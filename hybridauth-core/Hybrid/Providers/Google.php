<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like Facebook,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/

require_once Hybrid_Auth::$config["path_providers"] . "/OpenID.php";

/**
 * Hybrid_Providers_Google class, wrapper for Google user accounts and hosted apps 
 */
class Hybrid_Providers_Google extends Hybrid_Providers_OpenID
{
	var $googleIdentifiers = Array(
								"Users" => "https://www.google.com/accounts/o8/id", 
								"Apps" 	=> "https://www.google.com/accounts/o8/site-xrds?hd={hosted_domain_name}"
							);
	var $openidIdentifier  = NULL;
	var $hostedDomain      = NULL; // hosted_domain_name for Google "Apps" (hosted) accounts 

   /**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		if( ! isset(  $this->params["google_service"] ) )
		{
			$this->params["google_service"] = "Users";
		}

		parent::initialize();
	}

   /**
	* begin login step
	* 
	* google_service must be "User" for Google user accounts service or "Apps" for Google hosted Apps
	* if chosen google_service eq "Apps", google_hosted_domain parameter will be required
	*
	* build an normalized OpenID url to autentify the user with selected google_service openid identifier
	*/
	function loginBegin( )
	{
		# google_service must be "Users" for Google users accounts service or "Apps" for Google hosted Apps
		if( $this->params["google_service"] == "Users" )
		{
			$this->openidIdentifier = $this->googleIdentifiers["Users"];
		}
		elseif( $this->params["google_service"] == "Apps" )
		{
			# if chosen google_service eq "Apps", google_hosted_domain parameter will be required
			if( isset( $this->params["google_hosted_domain"] ) && ! empty( $this->params["google_hosted_domain"] ) )
			{
				$this->hostedDomain = $this->params["google_hosted_domain"];
			}
			else
			{
				throw new Exception( "Authentification failed! Missing reuqired parameter [google_hosted_domain] for hosted Apps service!", 5 );
			}

			$this->openidIdentifier = str_replace( "{hosted_domain_name}", $this->hostedDomain, $this->googleIdentifiers["Apps"] );
		}
		else
		{
			throw new Exception( "Authentification failed! Only Google [Users] and [Apps] accounts services are supported!", 5 );
		} 

		$this->service = $this->params["google_service"];

		parent::loginBegin();
	}
}
