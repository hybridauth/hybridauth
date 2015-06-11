<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2015 Hybridauth authors | https://hybridauth.github.io/license.html
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
