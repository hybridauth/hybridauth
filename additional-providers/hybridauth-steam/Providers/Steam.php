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

	try {
            $data = @file_get_contents($apiUrl);
            $data = @ new SimpleXMLElement($data);
	} catch(Exception $e) {
	    Hybrid_Logger::error( "Steam::getUserProfileLegacyAPI() error: ", $e->getMessage());
	    return false;
	}

        if (!is_object($data)) {
            return false;
        }

		# store the user profile.  
		//$this->user->profile->identifier		=	"";
		if (property_exists($data, 'customURL') && (string) $data->customURL != '') {
      			$this->user->profile->profileURL = 'http://steamcommunity.com/id/' . (string) $data->customURL . '/';
    		}
    		else {
            $this->user->profile->profileURL = "http://steamcommunity.com/profiles/{$this->user->profile->identifier}/";
    		}
		
		$this->user->profile->webSiteURL		=	"";
		$this->user->profile->photoURL			=	property_exists($data, 'avatarFull') ? (string)$data->avatarFull : '';
		$this->user->profile->displayName		=	property_exists($data, 'steamID') ? (string)$data->steamID : '';
		$this->user->profile->description		=	property_exists($data, 'summary') ? (string)$data->summary : '';
		$this->user->profile->firstName			=	property_exists($data, 'realname') ? (string)$data->realname : '';
		$this->user->profile->lastName			=	"";
		$this->user->profile->gender			=	"";
		$this->user->profile->language			=	"";
		$this->user->profile->age				=	"";
		$this->user->profile->birthDay			=	"";
		$this->user->profile->birthMonth		=	"";
		$this->user->profile->birthYear			=	"";
		$this->user->profile->email				=	"";
		$this->user->profile->emailVerified	    =	"";
		$this->user->profile->phone				=	"";
		$this->user->profile->address			=	"";
		$this->user->profile->country			=	"";
		$this->user->profile->region			=	property_exists($data, 'location') ? (string)$data->location : '';
		$this->user->profile->city				=	"";
		$this->user->profile->zip				=	"";
    }
}
