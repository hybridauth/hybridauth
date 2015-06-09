<?php
    require '../hybridauth_autoload.php';

    $config = [
        'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

        'openid_identifier' => 'https://open.login.yahooapis.com/openid20/www.yahoo.com/xrds',
        // 'openid_identifier' => 'https://openid.stackexchange.com/',
        // 'openid_identifier' => 'http://steamcommunity.com/openid',

        'debug_mode' => true,

        'debug_file' => __FILE__ . '.log',
    ];

    $adapter = new Hybridauth\Provider\OpenID( $config );

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
