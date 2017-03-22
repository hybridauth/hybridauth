<?php
/*!
* Details how to use users in a similar fashion to Hybridauth 2. Note that while Hybridauth 3 provides a similar interface to Hybridauth 2, both versions are not fully compatible with each other.
*/

include 'vendor/autoload.php';

use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;

$config = [
    'base_url' => HttpClient\Util::getCurrentUrl(),

    'providers' => [
        'OpenID' => [
            'enabled' => true
        ],

        'GitHub' => [ 
            'enabled' => true,
            'keys'    => [ 'id' => '', 'secret' => '' ], 
        ],

        'Google' => [ 
            'enabled' => true,
            'keys'    => [ 'id' => '', 'secret' => '' ],
        ],

        'Facebook' => [ 
            'enabled' => true,
            'keys'    => [ 'id' => '', 'secret' => '' ],
        ],

        'Twitter' => [ 
            'enabled' => true,
            'keys'    => [ 'key' => '', 'secret' => '' ],
        ],

        'Reddit' => [
            'enabled' => true,
            'keys'    => [ 'id' => '', 'secret' => '' ],
        ],
    ],

    /* optional : set debgu mode
        // You can also set it to
        // - false To disable logging
        // - true To enable logging
        // - 'error' To log only error messages. Useful in production
        // - 'info' To log info and error messages (ignore debug messages] 
        'debug_mode' => true,
        // 'debug_mode' => 'info',
        // 'debug_mode' => 'error',
        // Path to file writable by the web server. Required if 'debug_mode' is not false
        'debug_file' => __FILE__ . '.log', */

    /* optional : customize Curl settings
        // for more information on curl, refer to: http://www.php.net/manual/fr/function.curl-setopt.php  
        'curl_options' => [
            // setting custom certificates
            // http://curl.haxx.se/docs/caextract.html
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO         => '/path/to/your/certificate.crt',

            // setting proxies 
            # CURLOPT_PROXY          => '*.*.*.*:*',

            // custom user agent
            # CURLOPT_USERAGENT      => '', 

            // etc..
        ], */
];

$hybridauth = new Hybridauth( $config );

try {
    $adapter = $hybridauth->authenticate( 'GitHub' );

    // $adapter = $hybridauth->authenticate( 'Google' );
    // $adapter = $hybridauth->authenticate( 'Twitter' );
    // $adapter = $hybridauth->authenticate( 'Facebook' );
    // $adapter = $hybridauth->authenticate( 'Twitter' );
    // $adapter = $hybridauth->authenticate( 'Foursquare' );
    // $adapter = $hybridauth->authenticate( 'Disqus' );
    // $adapter = $hybridauth->authenticate( 'Reddit' );
    // $adapter = $hybridauth->authenticate( 'WordPress' );
    // $adapter = $hybridauth->authenticate( 'Steam' );
    // etc.

    $userProfile = $adapter->getUserProfile();
    $tokens = $adapter->getAccessToken();

    echo '<pre>';

    print_r( $userProfile ); 
    print_r( $tokens ); 
    print_r( $_SESSION );
}
catch( Exception $e ){
    echo $e->getMessage();
}

$adapter->disconnect();
