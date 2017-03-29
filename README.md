## [Hybridauth](https://hybridauth.github.io/) 3.0-dev

[![Build Status](https://travis-ci.org/hybridauth/hybridauth.svg?branch=3.0.0-Remake)](https://travis-ci.org/hybridauth/hybridauth) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hybridauth/hybridauth/badges/quality-score.png?b=3.0.0-Remake)](https://scrutinizer-ci.com/g/hybridauth/hybridauth/?branch=3.0.0-Remake) [![Latest Stable Version](https://poser.pugx.org/hybridauth/hybridauth/v/stable.png)](https://packagist.org/packages/hybridauth/hybridauth) [![Total Downloads](https://poser.pugx.org/hybridauth/hybridauth/downloads.png)](https://packagist.org/packages/hybridauth/hybridauth) [![License](https://poser.pugx.org/hybridauth/hybridauth/license.svg)](https://packagist.org/packages/hybridauth/hybridauth) 
[![Join the chat at https://gitter.im/hybridauth/hybridauth](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hybridauth/hybridauth?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)


    IMPORTANT: Hybridauth 3 is currently in development with the only remaning tasks of ensuring major providers are
    properly working and finishing documentation, hence it's NOT ready for production yet.

   :octocat: To check on project progression or if you wish to contribute on a task, see [TODO.md](https://github.com/hybridauth/hybridauth/blob/3.0.0-Remake/TODO.md).

Hybridauth enables developers to easily build social applications and tools to engage websites visitors and customers on a social level by implementing social sign-in, social sharing, users profiles, friends list, activities stream, status updates and more.

The main goal of Hybridauth is to act as an abstract API between your application and various social apis and identities providers such as Facebook, Twitter and Google.

#### Usage

Hybridauth provides a number of basic [examples](https://github.com/hybridauth/hybridauth/tree/master/examples). You can also find complete Hybridauth documentation at https://hybridauth.github.io

##### New way of doing things :

```php
    $config = [
        'callback' => 'http://localhost/hybridauth/examples/twitter.php',
        'keys' => [ 'key' => 'your-consumer-key', 'secret' => 'your-consumer-secret' ]
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

While HybridAuth 3 provides a similar interface to its functions, it's not backward compatible with Hybridauth 2. Please refer to [Upgrade guide](https://hybridauth.github.io/developer-ref-migrating.html) to make the neccessary changes to your existing application in order to make it work with HybridAuth 3.x.

```php
    $config = [
        'callback' => 'http://localhost/hybridauth/examples/callback.php',
        'providers' => [
            'GitHub' => [ 'enabled' => true, 'keys' => [ 'id' => '', 'secret' => '' ] ],
            'Google' => [ 'enabled' => true, 'keys' => [ 'id' => '', 'secret' => '' ] ]
        ]
    ];

    $hybridauth = new Hybridauth( $config );

    try{
        $github = $hybridauth->authenticate( 'GitHub' );

        $user_profile = $github->getUserProfile();

        echo "Hi there " . $user_profile->displayName;
    }
    catch( Exception $e ){
        echo "Ooophs, we ran into an issue! " . $e->getMessage();
    }
```

#### Requirements

* PHP 5.4+
* PHP Session

#### Installation

We recommend that you always use the latest release available at https://github.com/hybridauth/hybridauth/releases, but we also support installation using Composer.

When using Composer, you'll have to add Hybridauth to your project's existing composer.json file:

```bash
  $ php composer.phar require hybridauth/hybridauth
```

After installing, you need to require Composer's autoloader in your project:

```php
require 'vendor/autoload.php';
```

You can then later update Hybridauth to newer versions using composer:

```bash
  $ php composer.phar update
```

#### Versions Status

| Version | Status      | Repository              | Documentation           | PHP Version |
|---------|-------------|-------------------------|-------------------------|-------------|
| 2.x     | Maintenance | [v2][hybridauth-2-repo] | [v2][hybridauth-2-docs] | >= 5.3      |
| 3.x     | Development | [v3][hybridauth-3-repo] | [v3][hybridauth-3-docs] | >= 5.4      |

[hybridauth-2-repo]: https://github.com/hybridauth/hybridauth/
[hybridauth-3-repo]: https://github.com/hybridauth/hybridauth/tree/3.0.0-Remake
[hybridauth-2-docs]: http://hybridauth.github.io/hybridauth/
[hybridauth-3-docs]: http://hybridauth.github.io/

#### Questions, Help and Support?

For general questions (i.e, "how-to" questions), please consider using [StackOverflow](https://stackoverflow.com/questions/tagged/hybridauth) instead of the Github issues tracker. For convenience, we also have a [low-activity] mailing list at [Google Groups](http://groups.google.com/group/hybridauth) and a [Gitter channel](https://gitter.im/hybridauth/hybridauth) if you want to get help directly from the community.

#### License

Hybridauth PHP Library is released under the terms of MIT License.

For the full Copyright Notice and Disclaimer, see [COPYING.md](https://github.com/hybridauth/hybridauth/blob/master/COPYING.md).
