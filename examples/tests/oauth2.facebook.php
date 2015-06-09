<?php
    require '../hybridauth_autoload.php';

    $config = [
        'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

        'keys' => [ 'id' => '', 'secret' => '' ],

        'debug_mode' => true,

        'debug_file' => __FILE__ . '.log',

    /*
        // optional
            'tokens' => [
                'access_token' => 'your-facebook-access-token'
            ),

        // optional
            'endpoints' => [
                'api_base_url'      => 'https://graph.facebook.com/v2.2/',
                'authorize_url'     => 'https://www.facebook.com/dialog/oauth',
                'access_token_url'  => 'https://graph.facebook.com/oauth/access_token',
            )
    */
    ];

    $adapter = new Hybridauth\Provider\Facebook( $config );

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
