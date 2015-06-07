<?php
/*!
* HybridAuth
* http://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | http://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;

class Stackoverflow extends OpenID
{
    /**
    * {@inheritdoc}
    */
    protected $openidIdentifier = 'https://openid.stackexchange.com/';
}
