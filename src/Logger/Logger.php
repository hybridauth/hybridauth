<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Logger;

use Hybridauth\Exception;

/**
 * Debugging and Logging utility
 */
class Logger implements LoggerInterface
{
	/**
	* Debug level
	*
	* If you want to enable logging, set 'debug_mode' to:
	* 
	*     false  Disable logging.
	*      true  Enable logging. When set to TRUE, all logging levels will be saved in log file.
	*   "error"  Only log error messages.
	*    "info"  Log info and error messages (ignore debug messages).
	* 
	* @var mixed
	*/
	protected $debug_mode = false;

	/**
	* Path to file writeable by the web server. Required if 'debug_mode' is not false.
	*
	* @var string
	*/
	protected $debug_file = '';

	/**
	* @param $debug_mode
	* @param $debug_file
	*/
	function __construct( $debug_mode, $debug_file )
	{
 		if( ! $debug_mode )
		{
			return;
		}

		$this->initialize( $debug_mode, $debug_file );

		$this->debug_mode = $debug_mode;
		$this->debug_file = $debug_file;  
	}

	/**
	* @param mixed  $debug_mode
	* @param string $debug_file
	*
	* @throws Exception
	*/
	protected function initialize( $debug_mode, $debug_file )
	{
 		if( ! $debug_file )
		{
			throw new Exception( "'debug_mode' is set to 'true' but the log file path 'debug_file' is not set.", 1 );
		}

		if( ! file_exists( $debug_file ) && ! touch( $debug_file ) )
		{
			throw new Exception( "'debug_mode' is set to 'true', but the file 'debug_file' in 'debug_file' can not be created.", 1 );
		}

 		if( ! is_writable( $debug_file ) )
		{
			throw new Exception( "'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writeable file.", 1 );
		}
	}

	/**
	* {@inheritdoc}
	*/
	function info( $message )
	{
		if( 'error' === $this->debug_mode )
		{
			return;
		}

		$this->_write( 'INFO', $message );
	}

	/**
	* {@inheritdoc}
	*/
	function debug( $message, $object = null )
	{
		if( true !== $this->debug_mode )
		{
			return;
		}

		$this->_write( 'DEBUG', $message, $object );
	}

	/**
	* {@inheritdoc}
	*/
	function error($message, $object = null)
	{
		$this->_write( 'ERROR', $message, $object );
	}

	/**
	* Write a message to log file
	*
	* @param string $level Error level
	* @param string $message Error message
	* @param mixed  $object
	*/
	private function _write($level, $message, $object = null)
	{
		if( ! $this->debug_mode )
		{
			return;
		}

		$datetime = new \DateTime();
		$datetime =  $datetime->format(DATE_ATOM);

		$content  = $level . " -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . " -- ";
		$content .= ( $object ? print_r( $object, true ) : "" );
		$content .= "\n";

		file_put_contents( $this->debug_file, $content, FILE_APPEND );
	}
}
