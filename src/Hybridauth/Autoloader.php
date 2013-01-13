<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * HybridAuthAutoloader
 */
class HybridAuth_Autoloader
{
    /**
     * Handles autoloading of classes
     * @param string $className Name of the class to load
     */
    public static function autoload($className)
    {
        if ("Hybridauth" === substr($className, 0, strlen("Hybridauth"))&& "Hybridauth" != $className) {
            $fileName = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
            $namespace = '';
            if (false !== ($lastNsPos = strripos($className, '\\'))) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            
			if( file_exists($fileName) ){
				require $fileName;
			}
			else{
				throw new Exception( "HybridAuth_Autoloader($fileName) failed to open stream." );
			}
        }
    }
}

ini_set('unserialize_callback_func', 'spl_autoload_call');
spl_autoload_register(array(new HybridAuth_Autoloader, 'autoload'));
