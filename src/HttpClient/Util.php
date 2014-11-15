<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\HttpClient;

use Hybridauth\Data; 

class Util
{ 
	/**
	* Redirect to a given URL
	*/
	public static function redirect( $url )
	{
		header( "Location: $url" );

		exit;
	}

	// --------------------------------------------------------------------

	/**
	* Returns the Current URL 
	*
	* @param boolean $requestUri TRUE to get $_SERVER['REQUEST_URI'], FALSE for $_SERVER['PHP_SELF']
	*
	* @return string
	*/
	public static function getCurrentUrl( $requestUri = true  )
	{
		$collection = new Data\Collection( $_SERVER );
		
		$protocol = 'http://';

		if(
			$collection->exists( 'HTTPS' ) && ( $collection->get( 'HTTPS' ) == 'on' || $collection->get( 'HTTPS' ) == 1 )
		|| 	$collection->exists( 'HTTP_X_FORWARDED_PROTO' ) && $collection->get( 'HTTP_X_FORWARDED_PROTO' ) == 'https'
		){
			$protocol = 'https://';
		}

		$url = $protocol . $collection->get( 'HTTP_HOST' );

		if( $requestUri )
		{
			$url .= $collection->get( 'REQUEST_URI' );
		}
		else
		{
			$url .= $collection->get( 'PHP_SELF' );
		}

		return $url;
	}
}
