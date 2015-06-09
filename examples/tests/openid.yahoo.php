<?php
    require '../hybridauth_autoload.php';

    $config = [
        'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

        'debug_mode' => true,

        'debug_file' => __FILE__ . '.log',
    ];

    $adapter = new Hybridauth\Provider\YahooOpenID( $config );

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
