<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\User;

/**
 * Hybrid_User_Contact 
 * 
 * used to provider the connected user contacts list on a standardized structure across supported social apis.
 * 
 * http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Contacts.html
 */
class Contact
{
	/* The ID (name) of the connected provider */
	public $providerId = NULL;

	/* The Unique contact user ID */
	public $identifier = NULL;

	/* User website, blog, web page */ 
	public $webSiteURL = NULL;

	/* URL link to profile page on the IDp web site */
	public $profileURL = NULL;

	/* URL link to user photo or avatar */
	public $photoURL = NULL;

	/* User dispalyName provided by the IDp or a concatenation of first and last name */
	public $displayName = NULL;

	/* A short about_me */
	public $description = NULL;

	/* User email. Not all of IDp garant access to the user email */
	public $email = NULL;
}
