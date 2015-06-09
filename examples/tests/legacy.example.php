<?php
    require '../hybridauth_autoload.php';

    use Hybridauth\Hybridauth;

    $config = include 'legacy.config.php';

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
        echo $e->debug( $adapter ) ;

        $adapter->disconnect();
    }

    $adapter->disconnect();
