<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

class Response
{
	protected $body         = null;
	protected $statusCode   = null;
	protected $errorCode    = null;
	protected $curlHttpInfo = null;

	// --------------------------------------------------------------------

	function getBody()
	{
		return $this->body;
	}

	// --------------------------------------------------------------------

	function getStatusCode()
	{
		return $this->statusCode;
	}

	// --------------------------------------------------------------------

	function getErrorCode()
	{
		return $this->errorCode;
	}

	// --------------------------------------------------------------------

	function setBody($body)
	{
		$this->body = $body;
	}

	// --------------------------------------------------------------------

	function setStatusCode($statusCode)
	{
		$this->statusCode = $statusCode;
	}

	// --------------------------------------------------------------------

	function setErrorCode($errorCode)
	{
		$this->errorCode = $errorCode;
	}

	// --------------------------------------------------------------------

	function setCurlHttpInfo($curlHttpInfo)
	{
		$this->curlHttpInfo = $curlHttpInfo;
	}
}
