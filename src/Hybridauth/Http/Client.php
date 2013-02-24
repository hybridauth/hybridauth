<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

class Client implements ClientInterface
{
    public function get($uri, $params = array(), $headers = array(), $body = null)
    {
        if( empty( $uri ) ){
            return false;
        }

        $this->request = new \Hybridauth\Http\Request();

        $this->request->uri    = $uri;
        $this->request->method = 'GET';
        $this->request->params = $params;

        if ( ! empty( $headers ) && is_array( $headers ) ){
            $this->request->headers = $headers;
        }

        if ( ! empty( $body ) ){
            $this->request->body = $body;
        }

		$this->request->curlOptions = (array) $this->curlOptions;

        return $this->response = $this->request->send();
    }

	// --------------------------------------------------------------------

    public function post($uri, $params, $headers = array(), $body = null)
    {
        if( empty( $uri ) ){
            return false;
        }

        $this->request = new \Hybridauth\Http\Request();

        $this->request->uri    = $uri;
        $this->request->method = 'POST';
        $this->request->params = $params;

        if ( ! empty( $headers ) && is_array( $headers ) ){
            $this->request->headers = $headers;
        }

        if ( ! empty( $body ) ){
            $this->request->body = $body;
        }

		$this->request->curlOptions = $this->curlOptions;

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
