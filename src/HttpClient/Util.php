<?php
/*!
* HybridAuth
* http://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | http://hybridauth.github.io/license.html
*/

namespace Hybridauth\HttpClient;

use Hybridauth\Data;

class Util
{
    /**
    * Redirect to a given URL
    *
    * @param string $url
    */
    public static function redirect($url)
    {
        header("Location: $url");

        exit(1);
    }

    // --------------------------------------------------------------------

    /**
    * Returns the Current URL
    *
    * @param boolean $requestUri TRUE to get $_SERVER['REQUEST_URI'], FALSE for $_SERVER['PHP_SELF']
    *
    * @return string
    */
    public static function getCurrentUrl($requestUri = true)
    {
        $collection = new Data\Collection($_SERVER);
        
        $protocol = 'http://';

        if ($collection->exists('HTTPS')
            &&  ($collection->get('HTTPS') == 'on' || $collection->get('HTTPS') == 1)
            ||  $collection->exists('HTTP_X_FORWARDED_PROTO')
            &&  $collection->get('HTTP_X_FORWARDED_PROTO') == 'https'
        ) {
            $protocol = 'https://';
        }

        $basUrl = $protocol . $collection->get('HTTP_HOST');

        if ($requestUri) {
            return $basUrl . $collection->get('REQUEST_URI');
        }

        return $basUrl . $collection->get('PHP_SELF');
    }
}
