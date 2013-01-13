<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * The Hybrid_User class represents the current loggedin user 
 */
class Hybridauth_Core_User 
{
	/* The ID (name) of the connected provider */
	public $providerId = NULL;

	/* timestamp connection to the provider */
	public $timestamp = NULL; 

	/* user profile, containts the list of fields available in the normalized user profile structure used by HybridAuth. */
	public $profile = NULL;

	/**
	* inisialize the user object,
	*/
	function __construct()
	{
		$this->timestamp = time(); 

		$this->profile   = new Hybridauth_Core_User_Profile(); 
	}
}
