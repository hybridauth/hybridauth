<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2013, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_Pinterest (By Eduardo Marcolino - https://github.com/eduardo-marcolino)
 */
class Hybrid_Providers_Pinterest extends Hybrid_Provider_Model_OAuth2
{
  public $scope = "read_public,write_public,read_relationships,write_relationships";
  
  function initialize() 
  {
    parent::initialize();

    $this->api->api_base_url = "https://api.pinterest.com/v1/";
    $this->api->authorize_url = "https://api.pinterest.com/oauth/";
    $this->api->token_url = "https://api.pinterest.com/v1/oauth/token";
    $this->api->sign_token_name = "access_token";
  }
  
  function loginBegin() 
  {
    Hybrid_Auth::redirect($this->api->authorizeUrl(array(
        'scope' => isset($this->config['scope']) ? $this->config['scope'] : $this->scope,
        'response_type' => 'code',
        'client_id' => $this->api->client_id,
        'redirect_uri' => $this->api->redirect_uri,
        'state' => isset($this->config['state']) ? $this->config['state'] : ''
    )));
  }
  
  function getUserProfile() {
    $profile = $this->api->api("me/", "GET", array(
        'fields' => 'id,username,first_name,last_name,counts,image'
    ));

    if (!isset($profile->data->id)) {
         throw new Exception("User profile request failed! {$this->providerId} returned an invalid response:" . Hybrid_Logger::dumpData( $profile ), 6);
    }

    $data = $profile->data;

    $this->user->profile->identifier = $data->id;
    $this->user->profile->firstName = $data->first_name;
    $this->user->profile->lastName = $data->last_name;
    $this->user->profile->displayName = $data->username;
    
    if (isset($data->image->{'60x60'})) {
      $this->user->profile->photoURL = $data->image->{'60x60'}->url;
    }
    
    return $this->user->profile;
  }
  
}
