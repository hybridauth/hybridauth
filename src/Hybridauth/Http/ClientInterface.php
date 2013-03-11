<?php 
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Http;

interface ClientInterface
{
	public function get($url, $params = array(), $headers = array(), $body = null);

	// --------------------------------------------------------------------

	public function post($url, $params, $headers = array(), $body = null);

	// --------------------------------------------------------------------

	public function getState();

	// --------------------------------------------------------------------

	public function getResponseBody();

	// --------------------------------------------------------------------

	public function getResponseStatus();

	// --------------------------------------------------------------------

	public function getResponseError();
}
