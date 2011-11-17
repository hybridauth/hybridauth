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

/**
 * The Hybrid_User class represents the current loggedin user
 *
 * Note: As with all APIs, we are limited by the amout of data which the API provider provides us. 
 */
class Hybrid_User 
{
   	/**
	* The ID (name) of the connected provider
	*/
	public $providerId   = NULL;

   	/**
	* timestamp connection to the provider
	*/
	public $timestamp    = NULL; 

   	/**
	* user profile, containts the list of fields available in the normalized user profile structure used by HybridAuth.
	*/
	public $profile      = NULL;

  	/**
	* user contacts list, for future use
	*/
	# for future use, HybridAuth dont provide users contats on this version
	#     var $contacts     = NULL;

   	/**
	* inisialize the user object,
	*/
	function __construct()
	{
		$this->timestamp = time(); 

		$this->profile   = new Hybrid_User_Profile(); 
	}
}
