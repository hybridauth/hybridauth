<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

class Hybridauth_Core_Common_HTTP
{ 
	/**
	* Utility function, redirect to a given URL with php header or using javascript location.href
	*/
	public static function redirect( $url, $mode = "PHP" )
	{
		if( $mode == "PHP" ){
			header( "Location: $url" ) ;
		}
		elseif( $mode == "JS" ){
			echo '<html>';
			echo '<head>';
			echo '<script type="text/javascript">';
			echo 'function redirect(){ window.top.location.href="' . $url . '"; }';
			echo '</script>';
			echo '</head>';
			echo '<body onload="redirect()">';
			echo 'Redirecting, please wait...';
			echo '</body>';
			echo '</html>'; 
		}

		die();
	}

	// --------------------------------------------------------------------

	/**
	* Utility function, return the current url. TRUE to get $_SERVER['REQUEST_URI'], FALSE for $_SERVER['PHP_SELF']
	*/
	public static function getCurrentUrl( $request_uri = true ) 
	{
		if(
			isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1 )
		|| 	isset( $_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
		){
			$protocol = 'https://';
		}
		else {
			$protocol = 'http://';
		}

		$url = $protocol . $_SERVER['HTTP_HOST'];

		// use port if non default
		$url .= 
			isset( $_SERVER['SERVER_PORT'] )
			&&( ($protocol === 'http://' && $_SERVER['SERVER_PORT'] != 80 && !isset( $_SERVER['HTTP_X_FORWARDED_PROTO'])) || ($protocol === 'https://' && $_SERVER['SERVER_PORT'] != 443 && !isset( $_SERVER['HTTP_X_FORWARDED_PROTO'])) )
			? ':' . $_SERVER['SERVER_PORT'] 
			: '';

		if( $request_uri ){
			$url .= $_SERVER['REQUEST_URI'];
		}
		else{
			$url .= $_SERVER['PHP_SELF'];
		}

		// return current url
		return $url;
	}
}
