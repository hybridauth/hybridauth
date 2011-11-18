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
 * Debugging and Logging class
 */
class Hybrid_Logger
{
	function __construct()
	{
		if ( Hybrid_Auth::$config["debug_mode"] ){
			if ( ! file_exists( Hybrid_Auth::$config["debug_file"] ) ){
				throw new Exception( "'debug_mode' is set to 'true', but the given log file path 'debug_file' do not exist.", 1 );
			} 

			if ( ! is_writable( Hybrid_Auth::$config["debug_file"] ) ){
				throw new Exception( "'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writable file.", 1 );
			}
		} 
	}

	// --------------------------------------------------------------------

    /**
     * Log a message at the debug level.
     *
     * @param $message The message to log.
     */
    public static function debug( $message, $object = NULL )
	{
		if( Hybrid_Auth::$config["debug_mode"] )
		{
		    $datetime = new DateTime();
		    $datetime =  $datetime->format(DATE_ATOM);
    
			file_put_contents
			( 
				Hybrid_Auth::$config["debug_file"], 
				"DEBUG -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . " -- " . print_r($object, true) . "\n", 
				FILE_APPEND
			);
        }
    }

	// --------------------------------------------------------------------

    /**
     * Log a message at the info level.
     *
     * @param $message The message to log.
     */
    public static function info( $message )
	{ 
		if( Hybrid_Auth::$config["debug_mode"] )
		{
		    $datetime = new DateTime();
		    $datetime =  $datetime->format(DATE_ATOM);
    
			file_put_contents
			( 
				Hybrid_Auth::$config["debug_file"], 
				"INFO -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . "\n", 
				FILE_APPEND
			);
        }
    }

	// --------------------------------------------------------------------

    /**
     * Log a message at the error level.
     *
     * @param $message The message to log.
     */
    public static function error($message, $object = NULL)
	{ 
		if( Hybrid_Auth::$config["debug_mode"] )
		{
		    $datetime = new DateTime();
		    $datetime =  $datetime->format(DATE_ATOM);
    
			file_put_contents
			( 
				Hybrid_Auth::$config["debug_file"], 
				"ERROR -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . " -- " . print_r($object, true) . "\n", 
				FILE_APPEND
			);
        }
    }
}
