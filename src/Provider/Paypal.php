<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Paypal OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'     => [ 'id' => '', 'secret' => '' ],
 *       'scope'    => 'openid profile email',
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Paypal( $config );
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $profile = $adapter->getUserProfile();
 *   }
 *   catch( Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 */
class Paypal extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'openid profile email';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://www.paypal.com/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://api.paypal.com/v1/identity/openidconnect/tokenservice';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://developer.paypal.com/docs/api/overview/#';

    /**
    * {@inheritdoc}
    *
    * See: https://developer.paypal.com/docs/api/identity/v1/
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('v1/identity/oauth2/userinfo');
        $data = new Data\Collection($response);
        if (! $data->exists('user_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }
        $userProfile = new User\Profile();
        $userProfile->identifier  = $data->get('user_id');
        $userProfile->firstName   = $data->get('given_name');
        $userProfile->lastName    = $data->get('family_name');
        $userProfile->displayName = $data->get('name');
        $userProfile->address     = $data->filter('address')->get('street_address');
        $userProfile->city        = $data->filter('address')->get('locality');
        $userProfile->country     = $data->filter('address')->get('country');
        $userProfile->region      = $data->filter('address')->get('region');
        $userProfile->zip         = $data->filter('address')->get('postal_code');

        $emails = reset($data->filter('emails')->toArray());
        $userProfile->email       = emails['value'];

        $userProfile->emailVerified = ($data->get('verified_account') === true) ? $userProfile->email : '';

        return $userProfile;
    }
}
