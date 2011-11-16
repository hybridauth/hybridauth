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
	var $identifier 	= NULL;

   /**
	* User website, blog, web page, 
	*/	
	var $webSiteURL 	= NULL;

   /**
	* URL link to profile page on the IDp web site 
	*/
	var $profileURL 	= NULL;

   /**
	* URL link to user photo or avatar 
	*/	
	var $photoURL 		= NULL;

   /**
	* User dispalyName provided by the IDp or a concatenation of first and last name. 
	*/
	var $displayName 	= NULL;

   /**
	* A short about_me 
	*/
	var $description 	= NULL;

   /**
	* User's first name 
	*/
	var $firstName   	= NULL;

   /**
	* User's last name 
	*/
	var $lastName 		= NULL;

   /**
	* male or female 
	*/
	var $gender 		= NULL;

   /**
	* language
	*/
	var $language 		= NULL;

   /**
	* User age, we dont calculate it. we return it as is if the IDp provide it.
	*/
	var $age 			= NULL;

   /**
	* User birth Day, we dont calculate it. we return it as is if the IDp provide it.
	*/
	var $birthDay 		= NULL;

   /**
	* User birth Month, we dont calculate it. we return it as is if the IDp provide it.
	*/
	var $birthMonth 	= NULL;

   /**
	* User birth Year, we dont calculate it. we return it as is if the IDp provide it.
	*/
	var $birthYear 		= NULL;

   /**
	* User email. Not all of IDp garant access to the user email
	*/
	var $email 			= NULL;

   /**
	*  phone number
	*/
	var $phone 			= NULL;

   /**
	* complete user address
	*/
	var $address 		= NULL;

   /**
	* user country
	*/
	var $country 		= NULL;

   /**
	* region
	*/
	var $region			= NULL;

   /**
	*  city
	*/
	var $city 			= NULL;

   /**
	* Postal code or zipcode. 
	*/
	var $zip 			= NULL;
}
