<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2019 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Slack OAuth2 provider adapter.
 */
class Slack extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'identity.basic identity.email identity.avatar';

    /**
     {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://slack.com/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://slack.com/oauth/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://slack.com/api/oauth.access';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://api.slack.com/docs/sign-in-with-slack';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('api/users.identity');

        $data = new Data\Collection($response);

        if (! $data->exists('ok') || ! $data->get('ok')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->filter('user')->get('id');
        $userProfile->displayName = $data->filter('user')->get('name');
        $userProfile->email       = $data->filter('user')->get('email');
        $userProfile->photoURL    = $this->findLargestImage($data);

        return $userProfile;
    }

    private function findLargestImage(Data\Collection $data)
    {
        $maxSize = 0;
        foreach ($data->filter('user')->properties() as $property) {
            if (preg_match('^image_(\\d+)$', $property, $matches) === 1) {
                $availableSize = (int) $matches[1];
                if ($maxSize < $availableSize) {
                    $maxSize = $availableSize;
                }
            }
        }
        if ($maxSize > 0) {
            return $data->filter('user')->get('image_' . $maxSize);
        }
        return null;
    }

}
