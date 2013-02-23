<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

class Client
{
    public function get($url, $params = array(), $headers = array(), $body = null)
    {
        if (empty($url)) {
            return false;
        }

        $this->request = new \Hybridauth\Http\Request();

        $this->request->uri    = $url;
        $this->request->method = 'GET';
        $this->request->params = $params;

        if (!empty($headers) && is_array($headers)) {
            $this->request->headers = $headers;
        }

        if (!empty($body)) {
            $this->request->body = $body;
        }

		$this->request->curlOptions = $this->curlOptions;

        return $this->response = $this->request->send();
    }

    public function post($url, $params, $headers = array(), $body = null)
    {
        if (empty($url)) {
            return false;
        }

        $this->request = new \Hybridauth\Http\Request();

        $this->request->uri    = $url;
        $this->request->method = 'POST';
        $this->request->params = $params;

        if (!empty($headers) && is_array($headers)) {
            $this->request->headers = $headers;
        }

        if (!empty($body)) {
            $this->request->body = $body;
        }

		$this->request->curlOptions = $this->curlOptions;

		return $this->response = $this->request->send();
    }
}
