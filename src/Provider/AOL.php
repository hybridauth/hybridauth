<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;

final class AOL extends OpenID
{
    /**
     * {@inheritdoc}
     */
    protected $openidIdentifier = 'http://openid.aol.com/';
}
