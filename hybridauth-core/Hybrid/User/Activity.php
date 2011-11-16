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
	* activity/event id on the provider side
	*/	
	var $id 	= NULL;

   /**
	* activity/event date of creation
	*/	
	var $date 	= NULL;

   /**
	* activity/event content as string
	*/	
	var $text 	= NULL;

   /**
	* user who created the activity/event
	*/	
	var $user 	= NULL;

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
