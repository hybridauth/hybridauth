<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

class ApiBinding implements \Hybridauth\Adapter\ApiBindingInterface
{
	
	function bind( $action, $class )
	{
		$this->_binds[ $action ] = $class;
	}

	function __call( $action, $arguments )
	{
		if( isset( $this->_binds[ $action ] ) ){
			$tmp = new $this->_binds[ $action ]();

			$tmp->api = $this->api;

			return call_user_func_array( array( $tmp, $action ), $arguments );
		}
		else{
			throw new \Hybridauth\Exception( "Provider does not support this feature.", 8 );
		}
	}
}
