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
 * Hybrid_User_Activity 
 */
class Hybrid_User_Activity
{
   	/**
	* activity/event id on the provider side, usually given as integer
	*/	
	public $id 	= NULL;

  	/**
	* activity date of creation
	*/	
	public $date 	= NULL;

   	/**
	* activity content as string
	*/	
	public $text 	= NULL;

   	/**
	* user who created the activity 
	*/	
	public $user 	= NULL;

	public function __construct()
	{
		$this->user = new stdClass();

		// typically, we should have a few information about the user who created the event from social apis
		$this->user->identifier  = NULL;
		$this->user->displayName = NULL;
		$this->user->profileURL  = NULL;
		$this->user->photoURL    = NULL; 
	}
}
