<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------

function _include_class_file($Class_Name='')
{
	$class_file = dirname(__FILE__).'/'.str_replace('_', '/', $Class_Name).'.php';
	if(is_file($class_file))
	{
		require_once($class_file);
	}
	#echo $class_file;
}
spl_autoload_register('_include_class_file');

#require_once( "Hybrid/Auth.php" );
#require_once( "Hybrid/Endpoint.php" ); 

Hybrid_Endpoint::process();
