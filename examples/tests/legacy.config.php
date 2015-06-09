<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c] 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ----------------------------------------------------------------------------------------
//  HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

return 
    [
        'base_url' => Hybridauth\HttpClient\Util::getCurrentUrl(),

        'providers' => [
            'OpenID' => [
                'enabled' => true
            ],

            'Steam' => [
                'enabled' => true,
                'keys'    => [ 'secret' => '' ], 
            ],

            'GitHub' => [ 
                'enabled' => true,
                'keys'    => [ 'id' => '', 'secret' => '' ], 

        // optional
                'scope' => 'user:email',
            ],

            'Google' => [ 
                'enabled' => true,
                'keys'    => [ 'id' => '', 'secret' => '-' ],

        // optional
                // 'scope' => 'profile',
                // 'photo_size' => '150', 
            ],

            'Facebook' => [ 
                'enabled' => true,
                'keys'    => [ 'id' => '', 'secret' => '' ],

        // optional
                // 'api_version' => 'v2.0',
                // 'scope' => 'email, user_hometown',
                // 'photo_size' => '150',
                // 'force'   => true,
                // 'display' => 'popup',
            ],

            'Twitter' => [ 
                'enabled' => true,
                'keys'    => [ 'key' => '', 'secret' => '' ],

        // optional
                // 'api_version' => '1.1',
                // 'authorize' => true,
                // 'force_login' => true,
            ],

            'Foursquare' => [
                'enabled' => true,
                'keys'    => [ 'id' => '', 'secret' => '' ],

        // optional
                // 'api_version' => '20120610'
                // 'photo_size' => '150',
            ],

            'Disqus' => [
                'enabled' => true,
                'keys'    => [ 'id' => '', 'secret' => '' ],
            ],

            'Reddit' => [
                'enabled' => true,
                'keys'    => [ 'id' => '', 'secret' => '' ],
            ],

            'WordPress' => [
                'enabled' => true,
                'keys'    => [ 'id' => '', 'secret' => '' ],
            ],
        ],

    // optional
        // You can also set it to
        // - false To disable logging
        // - true To enable logging
        // - 'error' To log only error messages. Useful in production
        // - 'info' To log info and error messages (ignore debug messages] 
        'debug_mode' => true,
        // 'debug_mode' => 'info',
        // 'debug_mode' => 'error',


        // Path to file writable by the web server. Required if 'debug_mode' is not false
        'debug_file' => __FILE__ . '.log',

    // optional
        // tweak default Http client curl settings
        // http://www.php.net/manual/fr/function.curl-setopt.php  
        'curl_options' => [
            // setting custom certificates
            // http://curl.haxx.se/docs/caextract.html
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO         => dirname(__FILE__) . '/ca-bundle.crt',

            // setting proxies 
            # CURLOPT_PROXY          => '*.*.*.*:*',

            // custom user agent
            # CURLOPT_USERAGENT      => '', 

            // etc..
        ],
    ];
