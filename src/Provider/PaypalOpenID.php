<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;
use Hybridauth\HttpClient;

final class PaypalOpenID extends OpenID
{
    /**
     * {@inheritdoc}
     */
    protected $openidIdentifier = 'https://www.sandbox.paypal.com/webapps/auth/server';

    /**
     * {@inheritdoc}
     */
    public function authenticateBegin()
    {
        $this->openIdClient->identity  = $this->openidIdentifier;
        $this->openIdClient->returnUrl = $this->endpoint;
        $this->openIdClient->required  = [
            'namePerson/prefix',
            'namePerson/first',
            'namePerson/last',
            'namePerson/middle',
            'namePerson/suffix',
            'namePerson/friendly',
            'person/guid',
            'birthDate/birthYear',
            'birthDate/birthMonth',
            'birthDate/birthday',
            'gender',
            'language/pref',
            'contact/phone/default',
            'contact/phone/home',
            'contact/phone/business',
            'contact/phone/cell',
            'contact/phone/fax',
            'contact/postaladdress/home',
            'contact/postaladdressadditional/home',
            'contact/city/home',
            'contact/state/home',
            'contact/country/home',
            'contact/postalcode/home',
            'contact/postaladdress/business',
            'contact/postaladdressadditional/business',
            'contact/city/business',
            'contact/state/business',
            'contact/country/business',
            'contact/postalcode/business',
            'company/name',
            'company/title',
        ];

        HttpClient\Util::redirect($this->openIdClient->authUrl());
    }
}
