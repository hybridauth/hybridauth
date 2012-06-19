<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_User_Work_History
 *
 * used to provider the connected user work history on a standardized structure across supported social apis.
 */
class Hybrid_User_Work_History
{
	/* The employer name */
	public $employer = NULL;

	/* The position name */
	public $position = NULL;

	/* The user's work history location */
	public $location = NULL;

	/* The user's work history start day */
	public $startDay = NULL;

	/* The user's work history start month */
	public $startMonth = NULL;

	/* The user's work history start year */
	public $startYear = NULL;

	/* The user's work history end day*/
	public $endDay = NULL;

	/* The user's work history end month */
	public $endMonth = NULL;

	/* The user's work history end year */
	public $endYear = NULL;

	/* The user's work history description */
	public $description = NULL;
}
