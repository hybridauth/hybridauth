<?php
/*!
* A simple example that shows how to use Guzzle as a Http Client for Hybridauth instead of PHP Curl extention.
*/

include 'vendor/autoload.php';

$config = [
    'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

    'keys' => [ 'id' => '', 'secret' => '' ],
];

$guzzle = new Hybridauth\HttpClient\Guzzle(null, [
    // 'verify'  => true, # Set to false to disable SSL certificate verification
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
    echo $e->getMessage();
}

$adapter->disconnect();
