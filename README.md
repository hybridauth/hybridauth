## [Hybridauth](https://hybridauth.github.io/) 3.0-dev

[![Build Status](https://travis-ci.org/hybridauth/hybridauth.svg?branch=3.0.0-Remake)](https://travis-ci.org/hybridauth/hybridauth) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/hybridauth/hybridauth/badges/quality-score.png?b=3.0.0-Remake)](https://scrutinizer-ci.com/g/hybridauth/hybridauth/?branch=3.0.0-Remake) [![Latest Stable Version](https://poser.pugx.org/hybridauth/hybridauth/v/stable.png)](https://packagist.org/packages/hybridauth/hybridauth)


    IMPORTANT: Hybridauth 3 is currently in beta stage and it might NOT be suitable for production use.

Hybridauth enables developers to easily build social applications and tools to engage websites visitors and customers on a social level that starts off with social sign-in and extends to social sharing, users profiles, friends lists, activities streams, status updates and more.

The main goal of Hybridauth is to act as an abstract API between your application and the various social networks APIs and identities providers such as Facebook, Twitter and Google.

#### Usage

Hybridauth provides a number of basic [examples](https://github.com/hybridauth/hybridauth/tree/master/examples). You can also find complete Hybridauth documentation at https://hybridauth.github.io

```php
$config = [
    'callback' => 'https://example.com/path/to/script.php',
    'keys' => [ 'key' => 'your-twitter-consumer-key', 'secret' => 'your-twitter-consumer-secret' ]
];

try {
    $twitter = new Hybridauth\Provider\Twitter($config);

    $twitter->authenticate();

    $accessToken = $twitter->getAccessToken();
    $userProfile = $twitter->getUserProfile();
    $apiResponse = $twitter->apiRequest( 'statuses/home_timeline.json' );
}
catch(\Exception $e){
    echo 'Oops, we ran into an issue! ' . $e->getMessage();
}
```

#### Requirements

* PHP 5.4+
* PHP Session
* PHP cURL

#### Installation

To install Hybridauth we recommend [Composer](https://getcomposer.org/), the now defacto dependency manager for PHP. Otherwise you may always use the latest release available at [Github](https://github.com/hybridauth/hybridauth/releases).

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
