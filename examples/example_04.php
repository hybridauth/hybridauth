<?php
/*!
* A simple example that shows how to connect users to providers using OpenID.
*/

include 'vendor/autoload.php';

$config = [
    'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

    'openid_identifier' => 'https://open.login.yahooapis.com/openid20/www.yahoo.com/xrds',
    // 'openid_identifier' => 'https://openid.stackexchange.com/',
    // 'openid_identifier' => 'http://steamcommunity.com/openid',
    // etc.
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
    echo $e->getMessage();
}

$adapter->disconnect();
