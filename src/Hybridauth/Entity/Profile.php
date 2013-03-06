<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Entity;

/**
* Model class representing a user profile. 
*
* http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Profile.html
*/
class Profile
{
	/* The Unique user's ID on the connected provider */
	protected $identifier = null;

	/* User website, blog, web page */
	protected $webSiteURL = null;

	/* URL link to profile page on the IDp web site */
	protected $profileURL = null;

	/* URL link to user photo or avatar */
	protected $photoURL = null;

	/* User dispalyName provided by the IDp or a concatenation of first and last name. */
	protected $displayName = null;

	/* A short about_me */
	protected $description = null;

	/* User's first name */
	protected $firstName = null;

	/* User's last name */
	protected $lastName = null;

	/* male or female */
	protected $gender = null;

	/* language */
	protected $language = null;

	/* User age, we dont calculate it. we return it as is if the IDp provide it. */
	protected $age = null;

	/* User birth Day */
	protected $birthDay = null;

	/* User birth Month */
	protected $birthMonth = null;

	/* User birth Year */
	protected $birthYear = null;

	/* User email. Note: not all of IDp garant access to the user email */
	protected $email = null;
	
	/* Verified user email. Note: not all of IDp garant access to verified user email */
	protected $emailVerified = null;

	/* phone number */
	protected $phone = null;

	/* complete user address */
	protected $address = null;

	/* user country */
	protected $country = null;

	/* region */
	protected $region = null;

	/* city */
	protected $city = null;

	/* Postal code  */
	protected $zip = null;

	// --------------------------------------------------------------------

	/**
	* For lazy ppl like me
	*/
	function __toString()
	{
		return json_encode( get_class_vars( __CLASS__ ) ) ;
	}

	// --------------------------------------------------------------------
	// A bunch of naive getters and setters for the fun of it
	// --------------------------------------------------------------------

	function setIdentifier( $identifier )
	{
		$this->identifier = $identifier;
	}

	// --------------------------------------------------------------------

	function setWebSiteURL( $webSiteURL )
	{
		$this->webSiteURL = $webSiteURL;
	}

	// --------------------------------------------------------------------

	function setProfileURL( $profileURL )
	{
		$this->profileURL = $profileURL;
	}

	// --------------------------------------------------------------------

	function setPhotoURL( $photoURL )
	{
		$this->photoURL = $photoURL;
	}

	// --------------------------------------------------------------------

	function setDisplayName( $displayName )
	{
		$this->displayName = $displayName;
	}

	// --------------------------------------------------------------------

	function setDescription( $description )
	{
		$this->description = $description;
	}

	// --------------------------------------------------------------------

	function setFirstName( $firstName )
	{
		$this->firstName = $firstName;
	}

	// --------------------------------------------------------------------

	function setLastName( $lastName )
	{
		$this->lastName = $lastName;
	}

	// --------------------------------------------------------------------

	function setGender( $gender )
	{
		$gender = strtolower( $gender );

		if( $gender != 'female' && $gender != 'male' ){
			return;
		}

		$this->gender = $gender;
	}

	// --------------------------------------------------------------------

	function setLanguage( $language )
	{
		$this->language = $language;
	}

	// --------------------------------------------------------------------

	function setAge( $age )
	{
		$this->age = (int) $age;
	}

	// --------------------------------------------------------------------

	function setBirthDay( $birthDay )
	{
		$this->birthDay = $birthDay;
	}

	// --------------------------------------------------------------------

	function setBirthMonth( $birthMonth )
	{
		$this->birthMonth = $birthMonth;
	}

	// --------------------------------------------------------------------

	function setBirthYear( $birthYear )
	{
		$this->birthYear = $birthYear;
	}

	// --------------------------------------------------------------------

	function setEmail( $email )
	{
		$this->email = $email;
	}

	// --------------------------------------------------------------------

	function setEmailVerified( $emailVerified )
	{
		$this->emailVerified = $emailVerified;
	}

	// --------------------------------------------------------------------

	function setPhone( $phone )
	{
		$this->phone = $phone;
	}

	// --------------------------------------------------------------------

	function setAddress( $address )
	{
		$this->address = $address;
	}

	// --------------------------------------------------------------------

	function setCountry( $country )
	{
		$this->country = $country;
	}

	// --------------------------------------------------------------------

	function setRegion( $region )
	{
		$this->region = $region;
	}

	// --------------------------------------------------------------------

	function setCity( $city )
	{
		$this->city = $city;
	}

	// --------------------------------------------------------------------

	function setZip( $zip )
	{
		$this->zip = $zip;
	}

	// ====================================================================

	function getIdentifier()
	{
		return $this->identifier;
	}

	// --------------------------------------------------------------------

	function getWebSiteURL()
	{
		return $this->webSiteURL;
	}

	// --------------------------------------------------------------------

	function getProfileURL()
	{
		return $this->profileURL;
	}

	// --------------------------------------------------------------------

	function getPhotoURL()
	{
		return $this->photoURL;
	}

	// --------------------------------------------------------------------

	function getDisplayName()
	{
		return $this->displayName;
	}

	// --------------------------------------------------------------------

	function getDescription()
	{
		return $this->description;
	}

	// --------------------------------------------------------------------

	function getFirstName()
	{
		return $this->firstName;
	}

	// --------------------------------------------------------------------

	function getLastName()
	{
		return $this->lastName;
	}

	// --------------------------------------------------------------------

	function getGender()
	{
		return $this->gender;
	}

	// --------------------------------------------------------------------

	function getLanguage()
	{
		return $this->language;
	}

	// --------------------------------------------------------------------

	function getAge()
	{
		return $this->age;
	}

	// --------------------------------------------------------------------

	function getBirthDay()
	{
		return $this->birthDay;
	}

	// --------------------------------------------------------------------

	function getBirthMonth()
	{
		return $this->birthMonth;
	}

	// --------------------------------------------------------------------

	function getBirthYear()
	{
		return $this->birthYear;
	}

	// --------------------------------------------------------------------

	function getEmail()
	{
		return $this->email;
	}

	// --------------------------------------------------------------------

	function getEmailVerified()
	{
		return $this->emailVerified;
	}

	// --------------------------------------------------------------------

	function getPhone()
	{
		return $this->phone;
	}

	// --------------------------------------------------------------------

	function getAddress()
	{
		return $this->address;
	}

	// --------------------------------------------------------------------

	function getCountry()
	{
		return $this->country;
	}

	// --------------------------------------------------------------------

	function getRegion()
	{
		return $this->region;
	}

	// --------------------------------------------------------------------

	function getCity()
	{
		return $this->city;
	}

	// --------------------------------------------------------------------

	function getZip()
	{
		return $this->zip;
	}
}
