<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

use Hybridauth\Http\ClientInterface;
use Hybridauth\Http\Request;

class Client implements ClientInterface
{
	protected $request  = null;
	protected $response = null;

	// --------------------------------------------------------------------

	function __construct( $curl_opts = array() )
	{
		$this->request = new Request();

		$this->request->setCurlOptions( $curl_opts );
	}

	// --------------------------------------------------------------------

	function get($uri, $args = array(), $headers = array(), $body = null)
	{
		return $this->response = $this->request->send( $uri, 'GET', $args, $headers, $body );
	}

	// --------------------------------------------------------------------

	function post($uri, $args, $headers = array(), $body = null)
	{
		return $this->response = $this->request->send( $uri, 'POST', $args, $headers, $body );
	}

	// --------------------------------------------------------------------

	function getResponse()
	{
		return $this->response;
	}

	// --------------------------------------------------------------------

	function getResponseBody()
	{
		return $this->response->getBody();
	}

	// --------------------------------------------------------------------

	function getResponseStatus()
	{
		return $this->response->getStatusCode();
	}

	// --------------------------------------------------------------------

	function getResponseError()
	{
		return $this->response->getErrorCode();
	}
}
