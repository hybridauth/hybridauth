<?php
/*!
* An example on how use Access Tokens to access providers APIs, and how to setup custom API endpoints.
*/

include 'vendor/autoload.php';

$config = [
    'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),

    'keys' => [ 'id' => '', 'secret' => '' ],

    'tokens' => [
        'access_token' => 'your-facebook-access-token'
    ],

    'endpoints' => [
        'api_base_url'     => 'https://graph.facebook.com/v2.2/',
        'authorize_url'    => 'https://www.facebook.com/dialog/oauth',
        'access_token_url' => 'https://graph.facebook.com/oauth/access_token',
    ]
];

$adapter = new Hybridauth\Provider\Facebook( $config );

try {
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
