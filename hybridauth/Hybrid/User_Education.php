<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_User_Education
 *
 * used to provider the connected user education on a standardized structure across supported social apis.
 */
class Hybrid_User_Education
{
	/* School name */
	public $school = NULL;

	/* School field */
	public $field = NULL;

	/* Degree obtained */
	public $degree = NULL;

	/* School type (college, high school, etc.) */
	public $type = NULL;

	/* School start day */
	public $startDay = NULL;

	/* School start month */
	public $startMonth = NULL;

	/* School start year */
	public $startYear = NULL;

	/* School end day */
	public $endDay = NULL;

	/* School end month */
	public $endMonth = NULL;

	/* School end year */
	public $endYear = NULL;
}
