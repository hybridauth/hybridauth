<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Debugging and Logging manager
 */
class Hybrid_Logger
{
	private static $logger = null;
	public static function get()
	{
		if ( isset( self::$logger ) ) {
			return self::$logger;
		}
		$logger = isset(Hybrid_Auth::$config['logger']) ? Hybrid_Auth::$config['logger'] : 'Hybrid_Loggers_Default';
		if ( ! is_a($logger, 'Hybrid_Loggers_iLogger', true) ) {
			throw new Exception('Defined logger must conform to the Hybrid_Loggers_iLogger interface');
		}

		return self::$logger = new $logger(Hybrid_Auth::$config);
	}

	public static function debug( $message, $object = NULL )
	{
		self::get()->debug($message,$object);
	}

	public static function info( $message )
	{
		self::get()->info($message);
	}

	public static function error($message, $object = NULL)
	{
		self::get()->error($message,$object);
	}
}
