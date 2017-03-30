<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_StackExchange
 */
class Hybrid_Providers_StackExchange extends Hybrid_Provider_Model_OAuth2
{ 
  // default permissions
  // no scope
  //public $scope = "";

  /**
  * IDp wrappers initializer 
  */
  function initialize() 
  {
    $this->compressed = true;
    parent::initialize();
    
    // Provider api end-points
    $this->api->api_base_url  = "https://api.stackexchange.com/2.2/";
    $this->api->authorize_url = "https://stackexchange.com/oauth";     
    $this->api->token_url     = "https://stackexchange.com/oauth/access_token";
    
  }

  /**
  * load the user profile from the IDp api client
  */
  function getUserProfile()
  {
    // refresh tokens if needed 
    $this->refreshToken();
    try{
      $response = $this->api->get( "me" , array('key' => $this->config['keys']['key'], 'site' => $this->config['site']));
    }
    catch(StackExchange $e){
      throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
    }
    // check the last HTTP status code returned
    if ($this->api->http_code != 200){
      throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
    }
    if (!is_object($response)){
      throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
    }
    //
    $data = $response->items[0];
    
    if (!isset($data->account_id)){
      throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
    }

    $this->user->profile->identifier  = @ $data->account_id; 
    $this->user->profile->displayName = @ $data->display_name;
    $this->user->profile->photoURL    = @ $data->profile_image;
    $this->user->profile->profileURL  = @ $data->link; 
    $this->user->profile->region      = @ $data->location;
    $this->user->profile->age         = @ $data->age;

    return $this->user->profile;
  }
}
