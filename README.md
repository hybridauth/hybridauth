## [Hybridauth](http://hybridauth.github.io/) 3.0.0-Remake

[![Build Status](https://travis-ci.org/hybridauth/hybridauth.svg?branch=3.0.0-Remake)](https://travis-ci.org/hybridauth/hybridauth) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hybridauth/hybridauth/badges/quality-score.png?b=3.0.0-Remake)](https://scrutinizer-ci.com/g/hybridauth/hybridauth/?branch=3.0.0-Remake) [![Latest Stable Version](https://poser.pugx.org/hybridauth/hybridauth/v/stable.png)](https://packagist.org/packages/hybridauth/hybridauth) [![Total Downloads](https://poser.pugx.org/hybridauth/hybridauth/downloads.png)](https://packagist.org/packages/hybridauth/hybridauth) [![License](https://poser.pugx.org/hybridauth/hybridauth/license.svg)](https://packagist.org/packages/hybridauth/hybridauth) 
[![Join the chat at https://gitter.im/hybridauth/hybridauth](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hybridauth/hybridauth?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


    IMPORTANT: This is a work in progress and subject to changes at any time.

Hybridauth enables developers to easily build social applications and tools to engage websites visitors and customers on a social level by implementing social sign-in, social sharing, users profiles, friends list, activities stream, status updates and more.

The main goal of Hybridauth is to act as an abstract API between your application and various social apis and identities providers such as Facebook, Twitter and Google.

You can find complete Hybridauth documentation at https://hybridauth.github.io

#### Usage

Hybridauth provides a number of basic [examples](https://github.com/hybridauth/hybridauth/tree/master/examples).

... 

##### New way of doing things :

```php
    require 'vendor/autoload.php';

    $config = [
        'callback' => 'http://localhost/hybridauth/examples/twitter.php',

        'keys' => [ 'key'    => 'your-consumer-key', 'secret' => 'your-consumer-secret' ]
    ];

    $twitter = new Hybridauth\Provider\Twitter( $config );

    try {
        $twitter->authenticate();

        $userProfile = $twitter->getUserProfile();

        $accessToken = $twitter->getAccessToken();

        $apiResponse = $twitter->apiRequest( 'statuses/home_timeline.json' );
    }
    catch( Exception $e ){
        echo "Ooophs, we ran into an issue! " . $e->getMessage();
    }
```

##### Legacy way (Similar to Hybridauth 2.x)

Please refer to [Upgrade guide](http://hybridauth.github.io/developer-ref-migrating.html) to make the neccessary changes to your existing application in order to make it work with HybridAuth 3.x.

```php
    require 'hybridauth_autoload.php';

    $config = array(
        'base_url'  => 'http://localhost/hybridauth/examples/callback.php',

        'providers' => array(
            'GitHub' => array(
                'enabled' => true,
                'keys'    => array ( 'id' => '', 'secret' => '' ),
            )
        )
    );

    $hybridauth = new Hybridauth( $config );

    try{
        $github = $hybridauth->authenticate( "GitHub" );

        $user_profile = $github->getUserProfile();

        echo "Hi there " . $user_profile->displayName;
    }
    catch( Exception $e ){
        echo "Ooophs, we ran into an issue! " . $e->getMessage();
    }
```

#### Requirements

* PHP 5.4
* PHP Session
* PHP CURL

#### Dependencies

Hybridauth depends on few external libraries that come already included in HybridAuth:

* [LightOpenID](https://gitorious.org/lightopenid)
* [OAuth Library](https://code.google.com/p/oauth/)

#### Installation

We recommend that you always use the latest release available at https://github.com/hybridauth/hybridauth/releases, but we also support installation using Composer.

Note: Please, avoid using the master branch, as we usually keep it for development.

When using Composer, you have to add Hybridauth to your project dependencies:

```
"require": {
    "hybridauth/hybridauth": "3.0.*"
}
```

Install Composer and Dependencies:

```
$ curl -s http://getcomposer.org/installer | php
$ php composer.phar install
```

#### Get Involved

Hybridauth is a community driven project and accepts contributions of code and documentation from the community. 

For more information, see http://hybridauth.github.io/getinvolved.html. 

#### Questions, Help and Support?

For general questions (i.e, "how-to" questions), please consider using [StackOverflow](https://stackoverflow.com/questions/tagged/hybridauth) instead of the Github issues tracker. For convenience, we also have a [low-activity] mailing list at [Google Groups](http://groups.google.com/group/hybridauth) and a [Gitter channel](https://gitter.im/hybridauth/hybridauth) if you want to get help directly from the community.

#### Project maintainers

* [miled](https://github.com/miled)
* [AdwinTrave](https://github.com/AdwinTrave)
* [SocalNick](https://github.com/SocalNick)

#### Thanks

Big thanks to everyone who have contributed to Hybridauth by submitting patches, new ideas, code reviews and constructive discussions.

The list of the awesome people who have contributed to Hybridauth on Github can be found at https://github.com/hybridauth/hybridauth/graphs/contributors

#### License

Hybridauth PHP Library is released under the terms of MIT License.

For the full Copyright Notice and Disclaimer, see [COPYING.md](https://github.com/hybridauth/hybridauth/blob/master/COPYING.md).
