<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Api;

interface ApiInterface
{
	function generateAuthorizeUri( $extras = array() );

	// --------------------------------------------------------------------

	function get( $uri, $args = array() );

	// --------------------------------------------------------------------

	function post( $uri, $args = array() );
}
