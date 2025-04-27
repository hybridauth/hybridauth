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
 * For this provider to work it is necessary to assign the "OpenID Connect Permissions",
 * even if you only use basic OAuth2.
 */

/**
 * Tpedu OAuth2 provider adapter.
 */
class Tpedu extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://ldap.tp.edu.tw/api/v2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://ldap.tp.edu.tw/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://ldap.tp.edu.tw/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://github.com/leejoneshane/tpeduSSO/blob/master/%E8%87%BA%E5%8C%97%E5%B8%82%E6%95%99%E8%82%B2%E4%BA%BA%E5%93%A1%E5%96%AE%E4%B8%80%E8%BA%AB%E5%88%86%E9%A9%97%E8%AD%89%E8%B3%87%E6%96%99%E4%BB%8B%E6%8E%A5%E6%89%8B%E5%86%8AV2.04.docx?raw=true';

    /**
     * Currently authenticated user
     */
    protected $userId = null;

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('profile');

        $data = new Data\Collection($response);

        if (!$data->exists('role')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        } elseif ($data->get('role') == '家長') {
            throw new UnexpectedApiResponseException('Parents can not login!');
        }
        
        $userProfile = new User\Profile();

        if ($data->get('role') == '學生') {
            $userProfile->identifier = $data->get('studentId');
            $userProfile->data['groups'] = ['學生', $data->get('class')];
        } else {
            $userProfile->identifier = $data->get('teacherId');
            $userProfile->data['groups'] = $data->get('unit');
            $userProfile->data['groups'][] = '教師';
        }
        $userProfile->displayName = $data->get('name');
        $userProfile->gender = $data->get('gender');
        $userProfile->language = 'zh_TW';
        $userProfile->phone = $data->get('mobile');
        $userProfile->email = $data->get('email');
        $userProfile->emailVerified = $data->get('email_verified') ? $userProfile->email : '';

        return $userProfile;
    }

}
