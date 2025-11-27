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
 * Seznam OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'scope' => 'identity, contact-phone, avatar',
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Seznam($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class Seznam extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://login.szn.cz/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.szn.cz/api/v1/oauth/auth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.szn.cz/api/v1/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://vyvojari.seznam.cz/oauth/doc';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('api/v1/user', 'GET', ['format' => 'json']);

        $data = new Data\Collection($response);

        if (!$data->exists('oauth_user_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('oauth_user_id');
        $userProfile->email = $this->getEmailFromCollection($data);
        $userProfile->firstName = $data->get('firstname');
        $userProfile->lastName = $data->get('lastname');
        $userProfile->photoURL = $data->get('avatar_url');
        $userProfile->phone = $data->get('contact_phone');

        return $userProfile;
    }

    private function getEmailFromCollection(Data\Collection $data)
    {
        $email = $data->get('email');
        if ($email !== NULL) {
            return $email;
        }

        $username = $data->get('username');
        $domain = $data->get('domain');
        if ($username === NULL || $domain === NULL) {
            return NULL;
        }

        return sprintf("%s@%s", $username, $domain);
    }

}
