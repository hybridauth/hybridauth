<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Debugging and Logging manager
 */
class Hybrid_Loggers_Default implements Hybrid_Loggers_iLogger
{
	private $filename,$enabled;

	function __construct($config)
	{
		// if debug mode is set to true, then check for the writable log file
		if ( $this->enabled = $config["debug_mode"] ){
			if ( ! file_exists( Hybrid_Auth::$config["debug_file"] ) ){
				throw new Exception( "'debug_mode' is set to 'true', but the file " . Hybrid_Auth::$config['debug_file'] . " in 'debug_file' does not exit.", 1 );
			}

			if ( ! is_writable( $config["debug_file"] ) ){
				throw new Exception( "'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writable file.", 1 );
			}

			$this->filename = Hybrid_Auth::$config['debug_file'];
		}
	}

	public function debug( $message, $object = NULL )
	{
		if( $this->enabled ){
			$datetime = new DateTime();
			$datetime =  $datetime->format(DATE_ATOM);

			file_put_contents(
				$this->filename,
				"DEBUG -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . " -- " . print_r($object, true) . "\n",
				FILE_APPEND
			);
		}
	}

	public function info( $message )
	{
		if( $this->enabled ){
			$datetime = new DateTime();
			$datetime =  $datetime->format(DATE_ATOM);

			file_put_contents(
				$this->filename,
				"INFO -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . "\n",
				FILE_APPEND
			);
		}
	}

	public function error($message, $object = NULL)
	{
		if( $this->enabled ){
			$datetime = new DateTime();
			$datetime =  $datetime->format(DATE_ATOM);

			file_put_contents(
				$this->filename,
				"ERROR -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . " -- " . print_r($object, true) . "\n",
				FILE_APPEND
			);
		}
	}
}
