<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Steam provider adapter based on OpenID protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Steam.html
 *
 * This class has been entirely reworked for the new Steam API (http://steamcommunity.com/dev)
 */
class Hybrid_Providers_Steam extends Hybrid_Provider_Model_OpenID
{
    var $openidIdentifier = "http://steamcommunity.com/openid";

    function loginFinish()
    {
        parent::loginFinish();

        $this->user->profile->identifier = str_ireplace("http://steamcommunity.com/openid/id/",
            "", $this->user->profile->identifier);

        if (!$this->user->profile->identifier) {
            throw new Exception("Authentication failed! {$this->providerId} returned an invalid user ID.", 5);
        }

        // If API key is not provided, use legacy API methods
        if (!empty($this->config['keys']['key'])) {
            $this->getUserProfileWebAPI($this->config['keys']['key']);
        } else {
            $this->getUserProfileLegacyAPI();
        }

        Hybrid_Auth::storage()->set("hauth_session.{$this->providerId}.user", $this->user);
    }

    function getUserProfileWebAPI($apiKey)
    {
        $apiUrl = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='
            . $apiKey . '&steamids=' . $this->user->profile->identifier;

        $data = @file_get_contents($apiUrl);
        $data = json_decode($data);

        if (!is_object($data) || !isset($data->response->players[0])) {
            return false;
        }

        // Get the first index in 'players' array
        $data = $data->response->players[0];

        $this->user->profile->displayName = property_exists($data, 'personaname') ? $data->personaname : '';
        $this->user->profile->firstName = property_exists($data, 'realname') ? $data->realname : '';
        $this->user->profile->photoURL = property_exists($data, 'avatarfull') ? $data->avatarfull : '';
        $this->user->profile->profileURL = property_exists($data, 'profileurl') ? $data->profileurl : '';
        $this->user->profile->country = property_exists($data, 'loccountrycode') ? $data->loccountrycode : '';
    }

    function getUserProfileLegacyAPI()
    {
        $apiUrl = 'http://steamcommunity.com/profiles/' . $this->user->profile->identifier . '/?xml=1';

        $data = @file_get_contents($apiUrl);
        $data = @ new SimpleXMLElement($data);

        if (!is_object($data)) {
            return false;
        }

        $this->user->profile->displayName = property_exists($data, 'steamID') ? (string)$data->steamID : '';
        $this->user->profile->firstName = property_exists($data, 'realname') ? $data->realname : '';
        $this->user->profile->photoURL = property_exists($data, 'avatarfull') ? $data->avatarfull : '';
        $this->user->profile->description = property_exists($data, 'summary') ? (string)$data->summary : '';
        $this->user->profile->region = property_exists($data, 'location') ? (string)$data->location : '';
        $this->user->profile->profileURL = property_exists($data, 'customURL')
                ? "http://steamcommunity.com/id/{$data->customURL}/"
                : "http://steamcommunity.com/profiles/{$this->user->profile->identifier}/";
    }
}
