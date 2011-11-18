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
 * Hybrid_User_Profile describe the list of fields available in the normalized user profile
 * structure used by HybridAuth.
 *
 * Hybrid_User_Profile can be accessed via Hybrid_User::$profile 
 */
class Hybrid_User_Profile
{
   	/**
	* The Unique user's ID on the connected provider
	*/	
	public $identifier 	= NULL;

   	/**
	* User website, blog, web page, 
	*/	
	public $webSiteURL 	= NULL;

   	/**
	* URL link to profile page on the IDp web site 
	*/
	public $profileURL 	= NULL;

   	/**
	* URL link to user photo or avatar 
	*/	
	public $photoURL 		= NULL;

   	/**
	* User dispalyName provided by the IDp or a concatenation of first and last name. 
	*/
	public $displayName 	= NULL;

   	/**
	* A short about_me 
	*/
	public $description 	= NULL;

   	/**
	* User's first name 
	*/
	public $firstName   	= NULL;

   	/**
	* User's last name 
	*/
	public $lastName 		= NULL;

   	/**
	* male or female 
	*/
	public $gender 		= NULL;

   	/**
	* language
	*/
	public $language 		= NULL;

   	/**
	* User age, we dont calculate it. we return it as is if the IDp provide it.
	*/
	public $age 			= NULL;

   	/**
	* User birth Day, we dont calculate it. we return it as is if the IDp provide it.
	*/
	public $birthDay 		= NULL;

   	/**
	* User birth Month, we dont calculate it. we return it as is if the IDp provide it.
	*/
	public $birthMonth 	= NULL;

   	/**
	* User birth Year, we dont calculate it. we return it as is if the IDp provide it.
	*/
	public $birthYear 		= NULL;

   	/**
	* User email. Not all of IDp garant access to the user email
	*/
	public $email 			= NULL;

   	/**
	*  phone number
	*/
	public $phone 			= NULL;

   	/**
	* complete user address
	*/
	public $address 		= NULL;

   	/**
	* user country
	*/
	public $country 		= NULL;

   	/**
	* region
	*/
	public $region			= NULL;

   	/**
	*  city
	*/
	public $city 			= NULL;

   	/**
	* Postal code or zipcode. 
	*/
	public $zip 			= NULL;
}
