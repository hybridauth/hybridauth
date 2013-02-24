<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

class Client implements ClientInterface
{
	public function __construct( $curl_opts = array() )
	{
		$this->request = new \Hybridauth\Http\Request();

		$this->request->curlOptions = $curl_opts;
	}

	// --------------------------------------------------------------------

	public function get($uri, $args = array(), $headers = array(), $body = null)
	{
		if( empty( $uri ) ){
			return false;
		}

		$this->request->uri    = $uri;
		$this->request->method = 'GET';
		$this->request->args   = $args;

		if ( ! empty( $headers ) && is_array( $headers ) ){
			$this->request->headers = $headers;
		}

		if ( ! empty( $body ) ){
			$this->request->body = $body;
		}

		return $this->response = $this->request->send();
	}

	// --------------------------------------------------------------------

	public function post($uri, $args, $headers = array(), $body = null)
	{
		if( empty( $uri ) ){
			return false;
		}

		$this->request->uri    = $uri;
		$this->request->method = 'POST';
		$this->request->args   = $args;

		if ( ! empty( $headers ) && is_array( $headers ) ){
			$this->request->headers = $headers;
		}

		if ( ! empty( $body ) ){
			$this->request->body = $body;
		}

		return $this->response = $this->request->send();
	}

	// --------------------------------------------------------------------

	public function getResponseBody()
	{
		return $this->response->body;
	}

	// --------------------------------------------------------------------

	public function getResponseStatus()
	{
		return $this->response->statusCode;
	}

	// --------------------------------------------------------------------

	public function getResponseError()
	{
		return $this->response->errorCode;
	}
}
