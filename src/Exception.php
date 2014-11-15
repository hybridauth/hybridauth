<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth;

/**
 * Hybrid_Exception
 */
class Exception extends \Exception
{
    public function __toString()
    {
		$string  = 'HybridAuth has encountered the following error: ' . $this->message . ".\n"; 
		$string .= 'More information about this error may be available in your server error log.' . /* now the follow up will be removed --> */ ' (or inspect the Exception: $e->debug()).';

		return $string;
    }

	/**
	* Shamelessly Borrowered from Slimframework
	*/
	function debug( $object )
	{
		$title   = 'Hybridauth Exception';
		$code    = $this->getCode();
		$message = $this->getMessage ();
		$file    = $this->getFile();
		$line    = $this->getLine();
		$trace   = $this->getTraceAsString ();

		$html  = sprintf ( '<h1>%s</h1>', $title );
		$html .= '<p>HybridAuth has encountered the following error:</p>';
		$html .= '<h2>Details</h2>';

		$html .= sprintf ( '<div><strong>Code:</strong> %s</div>', $code );

		$html .= sprintf ( '<div><strong>Message:</strong> %s</div>', $message );

		$html .= sprintf ( '<div><strong>File:</strong> %s</div>', $file );

		$html .= sprintf ( '<div><strong>Line:</strong> %s</div>', $line );

		$html .= '<h2>Trace</h2>';
		$html .= sprintf ( '<pre>%s</pre>', $trace );

		if ( $object )
		{
			$html .= '<h2>Debug</h2>';

			$html .= sprintf ( '<pre>%s</pre>', str_ireplace( get_class( $object ) . ' Object', '<b>' . get_class( $object ) . '</b> ' . "\n\t" . 'extends <b>' . get_parent_class( $object ) . '</b>', print_r ( $object, true ) ) );
		}

		$html .= '<h2>Session</h2>';
		$html .= sprintf ( '<pre>%s</pre>', print_r ( $_SESSION, true ) );

		return sprintf ( "<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html );
	}
}
