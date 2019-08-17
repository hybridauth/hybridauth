<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;
/**
 * Odnoklassniki OAuth2 provider adapter.
 * @see https://apiok.ru/en/
 *
 * Example:
 *
 *   $config = [
 *       'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'      => ['id' => '', 'key' => '', 'secret' => ''],
 *   ];
 *   $adapter = new Hybridauth\Provider\Odnoklassniki($config);
 *   try {
 *       if (!$adapter->isConnected()) {
 *           $adapter->authenticate();
 *       }
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       print $e->getMessage();
 *   }
 */
class Odnoklassniki extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $scope = 'VALUABLE_ACCESS;LONG_ACCESS_TOKEN;GET_EMAIL';
    
    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.ok.ru/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://connect.ok.ru/oauth/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://api.ok.ru/oauth/token.do';

    /**
     * Calculate request signature (sig).
     * 
     * @param array $parameters Request data
     * @return string
     * @see https://apiok.ru/en/dev/methods/
     */
    protected function calculateSignature(array $parameters)
    {
        // Take access_token away from the list of parameters, if applicable
        unset($parameters['access_token']);
        // Parameters are sorted lexicographically by keys
        ksort($parameters);
        // Parameters are joined in the format key=value
        foreach ($parameters as $key => $value) {
            $parameters[$key] = $key . '=' . $value;
        }
        
        $sessionSecretKey = md5($this->getStoredData('access_token') . $this->config->get('keys')['secret']);

        $sig = md5(implode($parameters) . strtolower($sessionSecretKey));
        // The sig value is changed to the lower case
        return strtolower($sig);
    }
    
    /**
    * {@inheritdoc}
    * @see https://apiok.ru/en/dev/methods/rest/users/users.getCurrentUser
    */
    public function getUserProfile()
    {
        $fields = [
            'uid', 'url_profile', 'first_name', 'last_name', 'name', 'gender', 
            'age', 'birthday', 'pic1024x768', 'location', 'locale', 'email',
        ];
        $parameters = [
            'access_token'    => $this->getStoredData('access_token'),
            'application_key' => $this->config->get('keys')['key'],
            'method'          => 'users.getCurrentUser',
            'fields'          => implode(',', $fields),
        ];
        $parameters['sig'] = $this->calculateSignature($parameters);

        $response = $this->apiRequest('fb.do', 'GET', $parameters);

        $data = new Data\Collection($response);

        if (! $data->exists('uid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier  = $data->get('uid');
        $userProfile->profileURL  = $data->get('url_profile');
        $userProfile->photoURL    = $data->get('pic1024x768');
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->gender      = $data->get('gender');
        $userProfile->language    = $data->get('locale');
        $userProfile->age         = $data->get('age');
        $userProfile->email       = $data->get('email');
        if ($data->get('birthday')) {
            list($userProfile->birthYear, $userProfile->birthMonth, $profile->birthDay) = explode('-', $data->get('birthday'));
        }
        if ($address = $data->get('location')) {
            $userProfile->address = implode(', ', $address);
            $userProfile->country = isset($address['country']) ? $address['country'] : null;
            $userProfile->region  = isset($address['region']) ? $address['region'] : null;
            $userProfile->city    = isset($address['city']) ? $address['city'] : null;
        }

        return $userProfile;
    }
}
