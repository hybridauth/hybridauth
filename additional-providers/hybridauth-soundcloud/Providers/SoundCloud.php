<?php

/**
 * Hybrid_Providers_SoundCloud - SoundCloud provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_SoundCloud extends Hybrid_Provider_Model_OAuth2
{
  // default permissions
  public $scope = "";
  
  public static $_profileData = null;
  
  /**
   * Initializer
   */
  function initialize()
  {
    parent::initialize();
    
    $this->api->api_base_url  = 'https://api.soundcloud.com';
    $this->api->authorize_url = 'https://api.soundcloud.com/connect';
    $this->api->token_url     = 'https://api.soundcloud.com/oauth2/token';
    
    $this->api->curl_authenticate_method = "POST";
    $this->api->sign_token_name          = "oauth_token";
  }
  
  /**
   * Begin login step
   */
  function loginBegin()
  {
    // redirect the user to the provider authentication url
    Hybrid_Auth::redirect($this->api->authorizeUrl());
  }
  
  /**
   * load the user profile from the IDp api client
   */
  function getUserProfile()
  {
    // refresh tokens if needed 
    $this->refreshToken();
    
    try {
      $response = $this->api->get("/me");
    }
    catch (SoundCloudException $e) {
      throw new Exception("User profile request failed! {$this->providerId} returned an error: $e", 6);
    }
    
    // check the last HTTP status code returned
    if ($this->api->http_code != 200) {
      throw new Exception("User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus($this->api->http_code), 6);
    }
    
    if (!is_object($response) || !isset($response->id)) {
      throw new Exception("User profile request failed! {$this->providerId} api returned an invalid response.", 6);
    }
    # store the user profile.  
    $this->user->profile->identifier    = (property_exists($response, 'id')) ? $response->id : "";
    $this->user->profile->profileURL    = "";
    $this->user->profile->webSiteURL    = (property_exists($response, 'website')) ? $response->website : "";
    $this->user->profile->photoURL      = "";
    $this->user->profile->displayName   = (property_exists($response, 'full_name')) ? $response->full_name : "";
    $this->user->profile->description   = (property_exists($response, 'description')) ? $response->description : "";
    $this->user->profile->firstName     = (property_exists($response, 'first_name')) ? $response->first_name : "";
    $this->user->profile->lastName      = (property_exists($response, 'last_name')) ? $response->last_name : "";
    $this->user->profile->gender        = "";
    $this->user->profile->language      = "";
    $this->user->profile->age           = "";
    $this->user->profile->birthDay      = "";
    $this->user->profile->birthMonth    = "";
    $this->user->profile->birthYear     = "";
    $this->user->profile->email         = "";
    $this->user->profile->emailVerified = "";
    $this->user->profile->phone         = "";
    $this->user->profile->address       = "";
    $this->user->profile->country       = (property_exists($response, 'country')) ? $response->country : "";
    $this->user->profile->region        = "";
    $this->user->profile->city          = (property_exists($response, 'city')) ? $response->city : "";
    $this->user->profile->zip           = "";
    
    return $this->user->profile;
  }
  
  /**
   * {@inheritdoc}
   */
  function getUserContacts() {
    // refresh tokens if needed
    $this->refreshToken();

    //
    $response = array();
    $contacts = array();
    try {
      $response = $this->api->get("/users/me/followings");
    } catch (SoundCloudException $e) {
      throw new Exception("User contacts request failed! {$this->providerId} returned an error: $e");
    }

    if (isset($response)) {
      foreach ($response as $contact) {
        $uc = new Hybrid_User_Contact();
        //
        $uc->identifier   = $contact->id;
        $uc->profileURL   = $contact->uri;
        //$uc->webSiteURL   = ;
        $uc->photoURL     = $contact->avatar_url;
        $uc->displayName  = $contact->full_name;
        $uc->description  = (isset($contact->description) ? ($contact->description) : (""));
        $uc->email        = "";
        //
        $contacts[] = $uc;
      }
    }
    return $contacts;
  }
}

