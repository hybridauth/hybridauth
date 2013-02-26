<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\User;

/**
 * Hybrid_User_Activity 
 * 
 * used to provider the connected user activity stream on a standardized structure across supported social apis.
 * 
 * http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Activity.html
 */
class Activity
{
	/* The ID (name) of the connected provider */
	public $providerId = NULL;

	/* activity id on the provider side, usually given as integer */
	public $id = NULL;

	/* activity date of creation */ 
	public $date = NULL;

	/* activity content as a string */ 
	public $text = NULL;

	/* user who created the activity */
	public $user = NULL;

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
