<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth;

class Exception extends \Exception
{
	const UNSPECIFIED_ERROR                = 0;
	const HYBRIAUTH_CONFIGURATION_ERROR    = 1;
	const PROVIDER_NOT_PROPERLY_CONFIGURED = 2;
	const UNKNOWN_OR_DISABLED_PROVIDER     = 3;
	const MISSING_APPLICATION_CREDENTIALS  = 4;
	const AUTHENTIFICATION_FAILED          = 5;
	const USER_PROFILE_REQUEST_FAILED      = 6;
	const USER_NOT_CONNECTED               = 7;
	const UNSUPPORTED_FEATURE              = 8;

	// --------------------------------------------------------------------

	public function __construct( $message = null, $code = 0, Exception $previous = null, $object = null )
	{
		parent::__construct($message, $code, $previous);

		$this->object = $object;

		echo
			$this->debug( $this );

		die();
	}

	// --------------------------------------------------------------------

	// Shamelessly Borrowered from Slimframework, but to be removed/moved
    protected function debug()
    {
        $title   = 'Hybridauth Exception';
        $code    = $this->getCode();
        $message = $this->getMessage();
        $file    = $this->getFile();
        $line    = $this->getLine();
        $trace   = $this->getTraceAsString();

        $html = sprintf('<h1>%s</h1>', $title);
        $html .= '<p>Hybridauth could not run because of the following error:</p>';
        $html .= '<h2>Details</h2>';
        $html .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($this));
        if ($code) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }
        if ($message) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', $message);
        }
        if ($file) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }
        if ($line) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }
        if ($trace) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', $trace);
        }
        if ( isset( $exception->object ) ) {
            $html .= '<h2>Object</h2>';
            $html .= sprintf('<pre>%s</pre>', print_r( $exception->object, 1 ) );
        }

        return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html);
    }
}
