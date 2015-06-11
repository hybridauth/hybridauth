<?php
/*!
* HybridAuth
* https://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | https://hybridauth.github.io/license.html
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

    /**
     * Returns the Current URL
     *
     * @param boolean $requestUri TRUE to use $_SERVER['REQUEST_URI'], FALSE for $_SERVER['PHP_SELF']
     *
     * @return string
     */
    public static function getCurrentUrl($requestUri = false)
    {
        $collection = new Data\Collection($_SERVER);

        $protocol = 'http://';

        if (
            (
                $collection->exists('HTTPS') && $collection->get('HTTPS') !== 'off'
            ) || (
                $collection->exists('HTTP_X_FORWARDED_PROTO') && $collection->get('HTTP_X_FORWARDED_PROTO') === 'https'
            )
        ) {
            $protocol = 'https://';
        }

        return $protocol.
               $collection->get('HTTP_HOST').
               $collection->get($requestUri ? 'REQUEST_URI' : 'PHP_SELF');
    }
}
