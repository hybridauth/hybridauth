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
 * WeChat International OAuth2 provider adapter.
 */
class WeChat extends OAuth2
{

    /**
     * {@inheritdoc}
     */
    protected $scope = 'snsapi_userinfo';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.wechat.com/sns/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://open.weixin.qq.com/connect/qrconnect';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.wechat.com/sns/oauth2/access_token';

    /**
     * Refresh Token Endpoint
     * @var string
     */
    protected $tokenRefreshUrl = 'https://api.wechat.com/sns/oauth2/refresh_token';

    /**
     * {@ịnheritdoc}
     */
    protected $accessTokenInfoUrl = 'https://api.wechat.com/sns/auth';

    /**
     * {@inheritdoc}
     */
    protected $tokenExchangeMethod = 'GET';

    /**
     * {@inheritdoc}
     */
    protected $tokenRefreshMethod = 'GET';

    /**
    * {@inheritdoc}
    */
    protected function initialize()
    {
        parent::initialize();

        $this->apiRequestParameters = [
            'appid' => $this->clientId,
            'secret' => $this->clientSecret
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $this->storeData('openid', $collection->get('openid'));
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $openid = $this->getStoredData('openid');

        $response = $this->apiRequest('userinfo', 'GET', ['openid' => $openid]);

        $data = new Data\Collection($response);

        if (!$data->exists('openid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('openid');
        $userProfile->displayName = $data->get('nickname');
        $userProfile->photoURL    = $data->get('headimgurl');
        $userProfile->city        = $data->get('city');
        $userProfile->region      = $data->get('province');
        $userProfile->country     = $data->get('country');
        $userProfile->gender      = ['', 'male', 'female'][(int)$data->get('sex')];

        return $userProfile;
    }

}
