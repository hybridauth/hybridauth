<?php
    require '../../vendor/autoload.php'; //< need composer for this one

    $config = [
        'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

        'keys' => [ 'id' => '', 'secret' => '' ],

        'debug_mode' => true,

        'debug_file' => __FILE__ . '.log',
    ];

    $guzzle = new Hybridauth\HttpClient\Guzzle(null, [
        'verify'  => dirname(__FILE__) . '/ca-bundle.crt',
        // 'headers' => [ 'User-Agent' => '...' ]
        // 'proxy' => ...
    ]);

    $adapter = new Hybridauth\Provider\Github( $config, $guzzle );

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
