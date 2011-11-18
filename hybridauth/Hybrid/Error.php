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
 * The Hybrid_Error manage and store error for HybridAuth 
 */
class Hybrid_Error
{
   	/**
	* store error in HybridAuth cache system
	*/
	public static function setError( $message, $code = NULL, $trace = NULL, $previous = NULL )
	{
		Hybrid_Logger::info( "Enter Hybrid_Error::setError( $message )" );

		Hybrid_Auth::storage()->set( "hauth_session.error.status"  , 1           );
		Hybrid_Auth::storage()->set( "hauth_session.error.message" , $message    );
		Hybrid_Auth::storage()->set( "hauth_session.error.code"    , $code       );
		Hybrid_Auth::storage()->set( "hauth_session.error.trace"   , $trace      );
		Hybrid_Auth::storage()->set( "hauth_session.error.previous", $previous   );
	}

   	/**
	* store error in HybridAuth cache system
	*/
	public static function clearError()
	{ 
		Hybrid_Logger::info( "Enter Hybrid_Error::clearError()" );
		
		Hybrid_Auth::storage()->delete( "hauth_session.error.status"   );
		Hybrid_Auth::storage()->delete( "hauth_session.error.message"  );
		Hybrid_Auth::storage()->delete( "hauth_session.error.code"     );
		Hybrid_Auth::storage()->delete( "hauth_session.error.trace"    );
		Hybrid_Auth::storage()->delete( "hauth_session.error.previous" );
	}

	// --------------------------------------------------------------------

   	/**
	* Checks to see if there is a an error.
	*
	* errors are stored in Hybrid::storage() Hybrid storage system
	* and not displayed directly to user 
	*
	* @return boolean True if there is an error.
	*/
	public static function hasError()
	{ 
		return 
			(bool) Hybrid_Auth::storage()->get( "hauth_session.error.status" );
	}

	// --------------------------------------------------------------------

   	/**
	* a naive error message getter
	*
	* @return string very short error message.
	*/
	public static function getErrorMessage()
	{ 
		return
			Hybrid_Auth::storage()->get( "hauth_session.error.message" );
	}

	// --------------------------------------------------------------------

   	/**
	* a naive error code getter
	*
	* @return int error code defined on Hybrid_Auth.
	*/
	public static function getErrorCode()
	{ 
		return 
			Hybrid_Auth::storage()->get( "hauth_session.error.code" );
	}

	// --------------------------------------------------------------------

   	/**
	* a naive error backtrace getter
	*
	* @return string detailled error backtrace as string.
	*/
	public static function getErrorTrace()
	{ 
		return 
			Hybrid_Auth::storage()->get( "hauth_session.error.trace" );
	}

	// --------------------------------------------------------------------

   	/**
	* a naive error backtrace getter
	*
	* @return string detailled error backtrace as string.
	*/
	public static function getErrorPrevious()
	{ 
		return 
			Hybrid_Auth::storage()->get( "hauth_session.error.previous" );
	}
}
