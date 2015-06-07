User authentication
===================

### New way of doing things :

```php
/**
* 1. Require the Hybridauth Library
*
* If you are using Composer, then `vendor/autoload.php` will autoload all the required classes for us,
* otherwise you may use the included Hybridauth PSR-4 compliant autoloader on the examples folder.
* To know more, refer to the installation section.
*/
include 'vendor/autoload.php'; // or include 'examples/hybridauth_autoload.php';

/**
* 2. Configuring your application
*
* Set the Authorization callback URL to http://example.com/hybridauth/examples/twitter.php.
* Understandably, you need to replace 'path/to/hybridauth' with the real path to this
* script.
*/
$config = [
    'callback' => 'http://example.com/hybridauth/examples/twitter.php',

    'keys' => [
        'key'    => 'your-consumer-key',
        'secret' => 'your-consumer-secret'
    ]
];

/**
* 3. Instantiate Twitter adapter using the array $config we just built.
*/
$twitter = new Hybridauth\Provider\Twitter( $config );

try {
    /**
    * 4. Logging the user in
    *
    * Hybridauth will attempt to negotiate with the Twitter api and authenticate the user. If the process
    * fails for whatever reason, then Hybridauth will throw an exception.
    *
    * If the user is authenticated, then subsequent calls to this method will be ignored (yield a boolean).
    * To know more, refer to Hybridauth full developer api.
    */
    $twitter->authenticate();

    # at this point the authentication process has succeeded, and we can proceed with our application logic.
    # the examples below are meant to give a quick overview for the kind actions that Hybridauth can execute
    # on behalf on the user.

    /**
    * 5. Retrieve the oauth access tokens
    */
    $accessToken = $twitter->getAccessToken();

    /**
    * 6. Retrieve the user profile
    */
    $userProfile = $twitter->getUserProfile();

    /**
    * 7. Retrieve the user contacts
    */
    $userContacts = $twitter->getUserContacts();

    /**
    * 8. Retrieve the user timeline
    */
    $apiResponse = $twitter->apiRequest( 'statuses/home_timeline.json' );

    // etc.
}
catch( Exception $e ){
    echo "Oops, we ran into an issue! " . $e->getMessage();
}
```

**Note:** Optionally you may redefine the providers api end-points.

```php
$config = [
    'callback' => 'http://example.com/hybridauth/examples/twitter.php',

    'keys' => [
        'key'    => 'your-consumer-key',
        'secret' => 'your-consumer-secret'
    ],

    // Optional: Redefine providers endpoints
    'endpoints' => [
        'api_base_url'      => 'https://api.twitter.com/1.1/',
        'authorize_url'     => 'https://api.twitter.com/oauth/authenticate',
        'request_token_url' => 'https://api.twitter.com/oauth/request_token',
        'access_token_url'  => 'https://api.twitter.com/oauth/access_token',
    ]
];
```

### Authenticating a user with a pair of access tokens


```php
$config = [
    'callback' => 'http://localhost/hybridauth/examples/twitter.php',

    'keys' => [
        'key'    => 'your-consumer-key',
        'secret' => 'your-consumer-secret'
    ],

    // Supply the twitter access tokens for the current user
    'tokens' => [
        'access_token'        => 'your-access-token',
        'access_token_secret' => 'your-access-token-secret',
    ]
];

$twitter = new Hybridauth\Provider\Twitter( $config );

try {
    /**
    * Retrieve the user profile
    *
    * Note that we didn't call `$twitter->authenticate()` as we already have the user access tokens and in
    * case these tokens has been revoked or expired, `$twitter->getUserProfile()` will throw an exception.
    * For more information, refer to Hybridauth full developer api.
    */
    $userProfile = $twitter->getUserProfile();
}
catch( Exception $e ){
    echo "Oops, we ran into an issue! " . $e->getMessage();
}
```


### Legacy way (Similar to Hybridauth 2.x)

**Note:** Please refer to [Migrating to 3.0+](developer-ref-migrating.html) to make the necessary changes to your existing application in order to make it work with HybridAuth 3.x.

```php
// 
$config = array(
    'base_url'  => 'http://localhost/hybridauth/examples/callback.php',

    'providers' => array(
        'GitHub' => array(
            'enabled' => true,
            'keys'    => array ( 'id' => '', 'secret' => '' ),
        )
    )
);

// 
$hybridauth = new Hybridauth( $config );

try{
    //
    $github = $hybridauth->authenticate( "GitHub" );

    // 
    $user_profile = $github->getUserProfile();
}
catch( Exception $e ){
    echo "Oops, we ran into an issue! " . $e->getMessage();
}
```
