<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

/**
 * 
 */
trait AdapterHelperTrait
{
	/**
	* Validate Signed API Requests responses
	*
	* Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
	* Hybridauth will consider any HTTP status code that is different than '200 OK' as an ERROR.
	*
	* @throws Exception
	*/
	protected function validateApiResponse()
	{
		if( $this->httpClient->getResponseClientError() )
		{
			throw new Exception( 'HTTP client error: ' . $this->httpClient->getResponseClientError() . '.' );
		}

		if( 200 != $this->httpClient->getResponseHttpCode() )
		{
			throw new Exception( 'HTTP error ' . $this->httpClient->getResponseHttpCode() . '. Raw Provider API response: ' . $this->httpClient->getResponseBody() . '.' );
		}
	}

	/**
	* need to move this method somewhere else... 
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
