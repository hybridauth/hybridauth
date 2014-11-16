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
interface AdapterInterface
{
	/**
	* Initiate the authentication authorization protocol
	*
	* @throws Exception
	* @return boolean
	*/
	function authenticate();

	/**
	* Returns TRUE if the user is authorized
	*/
	function isAuthorized();

	/**
	* Clear all access token in storage
	*
	* @return boolean
	*/
	function disconnect();

	/**
	* Retrieve the connected user profile
	*
	* @throws Exception
	* @return \Hybridauth\User\Profile
	*/
	function getUserProfile();

	/**
	* Retrieve the connected user contacts list
	*
	* @throws Exception
	* @return array of \Hybridauth\User\Contact
	*/
	function getUserContacts();

	/**
	* return the user activity stream
	*
	* @param string $stream
	*
	* @throws Exception
	* @return array of \Hybridauth\User\Activity
	*/
	function getUserActivity( $stream );

	/**
	* Post a status on user wall|timeline|blog|website|etc.
	*
	* @param string|array $status
	*
	* @throws Exception
	* @return mixed API response
	*/
	function setUserStatus( $status );
}
