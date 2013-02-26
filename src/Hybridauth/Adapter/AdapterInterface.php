<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

interface AdapterInterface
{
	function initialize();

	// --------------------------------------------------------------------

	function loginBegin();

	// --------------------------------------------------------------------

	function loginFinish();

	// --------------------------------------------------------------------

	function isUserConnected();

	// --------------------------------------------------------------------

	function setUserConnected();

	// --------------------------------------------------------------------

	function logout();
}
