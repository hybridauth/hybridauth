<?php
/*!
* HybridAuth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
* (c) 2017, HybridAuth authors | https://hybridauth.github.io/license.html
*/

/**
 * Hybrid_Providers_OpenStreetMap (openstreetmap.org)
 */
class Hybrid_Providers_OpenStreetMap extends Hybrid_Provider_Model_OAuth1
{
  /**
   * IDp wrappers initializer
   */
  function initialize()
  {
    parent::initialize();

    // provider api end-points
    $this->api->api_base_url      = "https://api.openstreetmap.org/api/0.6/";
    $this->api->authorize_url     = "https://www.openstreetmap.org/oauth/authorize";
    $this->api->request_token_url = "https://www.openstreetmap.org/oauth/request_token";
    $this->api->access_token_url  = "https://www.openstreetmap.org/oauth/access_token";

    // turn off json parsing!
    $this->api->decode_json = false;
  }

  /**
   * load the user profile from the api client
   */
  function getUserProfile()
  { 
    try {
      $response = $this->api->get( 'user/details', array(), "text/xml" );
    }
    catch( Exception $e ) {
      throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
    }

    // check the last HTTP status code returned
    if ( $this->api->http_code != 200 )
    {
      throw new Exception( "User profile request failed! {$this->providerId} returned an error: " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
    }

    $response = @ new SimpleXMLElement( $response );

    $this->user->profile->identifier    = (string) $response->user["id"];
    $this->user->profile->displayName   = (string) $response->user["display_name"];
    $this->user->profile->description   = (string) $response->user->description;
    $this->user->profile->photoURL      = (string) $response->user->img["href"];

    return $this->user->profile;
   }
}
