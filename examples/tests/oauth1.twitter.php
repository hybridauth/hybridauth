<?php
    require '../hybridauth_autoload.php';

    $config = [
        'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

        'keys' => [ 'key' => '', 'secret' => '' ],

        'debug_mode' => true,

        'debug_file' => __FILE__ . '.log',

    /*
        // optional
            'tokens' => [
                'access_token'        => 'your-access-token',
                'access_token_secret' => 'your-access-token-secret',
            ],

        // optional
            'endpoints' => [
                'api_base_url'      => 'https://api.twitter.com/1.1/',
                'authorize_url'     => 'https://api.twitter.com/oauth/authenticate',
                'request_token_url' => 'https://api.twitter.com/oauth/request_token',
                'access_token_url'  => 'https://api.twitter.com/oauth/access_token',
            ]
    */
    ];

    $adapter = new Hybridauth\Provider\Twitter( $config );

    try {
        $adapter->authenticate();

        $userProfile = $adapter->getUserProfile();
        $tokens = $adapter->getAccessToken();

        echo '<pre>';

        print_r( $userProfile ); 
        print_r( $tokens ); 
        print_r( $_SESSION );
    }
    catch( Exception $e ){
        echo $e->debug( $adapter ) ;

        $adapter->disconnect();
    }

    $adapter->disconnect();
