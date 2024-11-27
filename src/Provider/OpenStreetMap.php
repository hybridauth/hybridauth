<?php

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

class OpenStreetMap extends OAuth2
{
    protected $scope = 'read_prefs';
    protected $apiBaseUrl = 'https://api.openstreetmap.org/api/0.6/';
    protected $authorizeUrl = 'https://www.openstreetmap.org/oauth2/authorize';
    protected $accessTokenUrl = 'https://www.openstreetmap.org/oauth2/token';
    public function getUserProfile()
    {
  $response = $this->apiRequest('user/details');

  $data = new Data\Collection($response);
  $userData = $data->get('osm')['user'];

  if (!$userData) {
  	throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
  }

  $userProfile = new User\Profile();

  $userProfile->identifier = isset($userData['@attributes']['id']) ? $userData['@attributes']['id'] : null;
  $userProfile->displayName = isset($userData['@attributes']['display_name']) ? $userData['@attributes']['display_name'] : null;
  $userProfile->photoURL = isset($userData['img']['@attributes']['href']) ? $userData['img']['@attributes']['href'] : null;
  $userProfile->description = isset($userData['description']) ? $userData['description'] : null;

  return $userProfile;  
    }
}
