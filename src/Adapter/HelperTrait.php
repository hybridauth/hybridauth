<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

trait HelperTrait
{
	/**
	*
	*/
	function fetchBirthday( $userProfile, $birthday, $seperator  )
	{
		if( $birthday )
		{
			list( $birthday_year, $birthday_month, $birthday_day ) = explode( $seperator, $birthday );

			$userProfile->birthDay   = (int) $birthday_day;
			$userProfile->birthMonth = (int) $birthday_month;
			$userProfile->birthYear  = (int) $birthday_year;
		}

		return $userProfile;
	}
}
