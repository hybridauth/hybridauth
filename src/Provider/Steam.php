<?php
/*!
* HybridAuth
* http://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | http://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\User;

class Steam extends OpenID
{
    /**
    * {@inheritdoc}
    */
    protected $openidIdentifier = 'http://steamcommunity.com/openid';

    /**
    * {@inheritdoc}
    */
    public function loginFinish()
    {
        parent::loginFinish();

        $userProfile = $this->storage->get($this->providerId . '.user');

        $userProfile->identifier = str_ireplace('http://steamcommunity.com/openid/id/', '', $userProfile->identifier);

        if (! $userProfile->identifier) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $result = array();

        // if api key is provided, we attempt to use steam web api

        if ($this->config->filter('keys')->exists('secret')) {
            $result = $this->getUserProfileWebAPI($this->config->filter('keys')->get('secret'), $userProfile->identifier);
        }

        // otherwise we fallback to community data
        else {
            $result = $this->getUserProfileLegacyAPI($userProfile->identifier);
        }

        // fetch user profile
        foreach ($result as $k => $v) {
            $userProfile->$k = $v ? $v : $userProfile->$k;
        }

        // store user profile
        $this->storage->set($this->providerId . '.user', $userProfile);
    }

    /**
    *
    */
    public function getUserProfileWebAPI($apiKey, $steam64)
    {
        $apiUrl = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $apiKey . '&steamids=' . $steam64;

        $response = $this->httpClient->request($apiUrl);
        $data = json_decode($response);

        // not sure if correct
        $data = isset($data->response->players[0]) ? $data->response->players[0] : null;

        $userProfile = array();

        $userProfile['displayName'] = property_exists($data, 'personaname')    ? $data->personaname    : '';
        $userProfile['firstName'  ] = property_exists($data, 'realname')       ? $data->realname       : '';
        $userProfile['photoURL'   ] = property_exists($data, 'avatarfull')     ? $data->avatarfull     : '';
        $userProfile['profileURL' ] = property_exists($data, 'profileurl')     ? $data->profileurl     : '';
        $userProfile['country'    ] = property_exists($data, 'loccountrycode') ? $data->loccountrycode : '';

        return $userProfile;
    }

    /**
    *
    */
    public function getUserProfileLegacyAPI($steam64)
    {
        libxml_use_internal_errors(false);

        $apiUrl = 'http://steamcommunity.com/profiles/' . $steam64 . '/?xml=1';

        $response = $this->httpClient->request($apiUrl);

        $userProfile = array();

        try {
            $data = new \SimpleXMLElement($response);

            $userProfile['displayName' ] = property_exists($data, 'steamID')    ? (string) $data->steamID     : '';
            $userProfile['firstName'   ] = property_exists($data, 'realname')   ? (string) $data->realname    : '';
            $userProfile['photoURL'    ] = property_exists($data, 'avatarFull') ? (string) $data->avatarFull  : '';
            $userProfile['description' ] = property_exists($data, 'summary')    ? (string) $data->summary     : '';
            $userProfile['region'      ] = property_exists($data, 'location')   ? (string) $data->location    : '';
            $userProfile['profileURL'  ] = property_exists($data, 'customURL')
                                                ? "http://steamcommunity.com/id/{$data->customURL}/"
                                                : "http://steamcommunity.com/profiles/{$userProfile->identifier}/";
        }

        // these data are not mandatory, so keep it quite
        catch (\Exception $e) {
        }

        return $userProfile;
    }
}
