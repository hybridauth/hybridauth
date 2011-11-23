<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_User_Contact 
 */
class Hybrid_User_Contact
{
	/**
	* The Unique user's ID on the connected provider
	*/
	public $identifier = NULL;

	/**
	* User website, blog, web page, 
	*/	
	public $webSiteURL = NULL;

	/**
	* URL link to profile page on the IDp web site 
	*/
	public $profileURL = NULL;

	/**
	* URL link to user photo or avatar 
	*/	
	public $photoURL = NULL;

	/**
	* User dispalyName provided by the IDp or a concatenation of first and last name. 
	*/
	public $displayName = NULL;

	/**
	* A short about_me 
	*/
	public $description = NULL;

	/**
	* User email. Not all of IDp garant access to the user email
	*/
	public $email = NULL;
}
