<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\Exception;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data\Collection;
use Hybridauth\User\Profile;

/**
 * Yandex OAuth2 provider adapter.
 */
class Yandex extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://login.yandex.ru/info';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://oauth.yandex.ru/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.yandex.ru/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://yandex.com/dev/oauth/doc/dg/concepts/about-docpage/';

    /**
     * load the user profile from the IDp api client
     *
     * @throws Exception
     */
    public function getUserProfile()
    {
        $this->scope = implode(',', []);

        $response = $this->apiRequest($this->apiBaseUrl, 'GET', [ 'format' => 'json' ]);

        if (!isset($response->id)) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('real_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL = 'http://upics.yandex.net/' . $userProfile->identifier . '/normal';
        $userProfile->profileURL = "";
        $userProfile->gender = $data->get('sex');
        $userProfile->email = $data->get('default_email');
        $userProfile->emailVerified = $data->get('default_email');

        if ($data->get('birthday')) {
            list($birthday_year, $birthday_month, $birthday_day) = explode('-', $response->birthday);
            $userProfile->birthDay = (int)$birthday_day;
            $userProfile->birthMonth = (int)$birthday_month;
            $userProfile->birthYear = (int)$birthday_year;
        }

        return $userProfile;
    }
}
