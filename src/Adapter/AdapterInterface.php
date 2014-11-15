<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

/**
 * 
 */
interface AdapterInterface
{
	/**
	* Initiate the authentication authorization protocol
	*/
	function authenticate();

	/**
	* Initiate the authentication authorization protocol
	*/
	function isAuthorized();

	/**
	* Clear all access token in storage
	*/
	function disconnect();

   	/**
	* Retrieve the connected user profile
	*
	* @return User\Profile
	*/
	function getUserProfile();

	/**
	* Retrieve the connected user contacts list
	*
	* @throws Exception
	* @return array of User\Contact
	*/
	function getUserContacts();

	/**
	* return the user activity stream
	*
	* @param string $stream
	*
	* @throws Exception
	* @return array of User\Activity
	*/
	function getUserActivity( $stream );

	/**
	* Post a status on user wall|timeline|blog|website|etc.
	*
	* @param string|array $status
	*
	* @throws Exception
	* @return mixed api response
	*/
	function setUserStatus( $status );
}
