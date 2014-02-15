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
    const ERROR   = 1;
    const INFO    = 2;
    const DEBUG   = 3;

	function __construct()
	{
        // Ensure that a value is set, default to debug
        Hybrid_Auth::$config["debug_level"] = isset(Hybrid_Auth::$config["debug_level"])
            ? Hybrid_Auth::$config["debug_level"]
            : static::DEBUG;

		// if debug mode is set to true, then check for the writable log file
		if ( Hybrid_Auth::$config["debug_mode"] ){
			if ( ! file_exists( Hybrid_Auth::$config["debug_file"] ) ){
				throw new Exception( "'debug_mode' is set to 'true', but the file " . Hybrid_Auth::$config['debug_file'] . " in 'debug_file' does not exist.", 1 );
			}

			if ( ! is_writable( Hybrid_Auth::$config["debug_file"] ) ){
				throw new Exception( "'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writable file.", 1 );
			}
		} 
	}

    /**
     * Log a message.
     *
     * @param string $message The message to log.
     * @param int    $level   The log level (DEBUG, INFO, ERROR)
     * @param object $object  An option object to display.
     *
     * @return void
     */
    public static function log( $message, $level, $object = NULL )
    {
		if( Hybrid_Auth::$config["debug_mode"] && $level <= Hybrid_Auth::$config["debug_level"] ){
			$datetime = new DateTime();
			$datetime = $datetime->format(DATE_ATOM);

            $message = $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message;
            if ($object) {
                $message .=  " -- " . print_r($object, true);
            }
            $message = $message . "\n";

			file_put_contents( Hybrid_Auth::$config["debug_file"], $message, FILE_APPEND );
        }
    }

    /**
     * Emulates debug(), error() and info() messages.
     *
     * @param string $name The name of the function called.
     * @param array  $args The other arguments (a string message and optional
     *                     object to display).
     *
     * @return void
     */
    public static function __callStatic( $name, $args )
    {
        $priority = strtoupper($name);
        $level    = constant('static::' . $priority);

        if (empty($level)) {
            throw new Exception( 'Unknown log level "' . $name . '"' );
        }

        $message  = array_shift($args);
        $object   = array_shift($args);

        static::log( $priority . ' -- ' . $message, $level, $object );
    }
}
