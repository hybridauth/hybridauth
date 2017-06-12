<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

/**
 * Hybrid_Providers_Authentiq
 */
class Hybrid_Providers_Authentiq extends Hybrid_Provider_Model_OAuth2
{
  /**
   * {@inheritdoc}
   */
  public $scope = 'openid aq:name email~rs phone~s address aq:push';

  /**
  * IDp wrappers initializer
  */
  function initialize()
  {
    parent::initialize();

    // Provider api end-points
    $this->api->api_base_url  = 'https://connect.authentiq.io/';
    $this->api->authorize_url = 'https://connect.authentiq.io/authorize';
    $this->api->token_url     = 'https://connect.authentiq.io/token';

    if( $this->token( 'access_token' ) )
    {
      $this->api->curl_header = array( 'Authorization: Bearer ' . $this->token( 'access_token' ) );
    }
  }

  /**
   * begin login step
   */
  function loginBegin()
  {
    // redirect the user to the provider authentication url
    Hybrid_Auth::redirect( $this->api->authorizeUrl( array( "scope" => $this->scope, "state" => md5( mt_rand() ) ) ) );
  }

  /**
   * load the user profile from the IDp api client
   */
  function getUserProfile()
  {
    $data = $this->api->api('userinfo');

    if (!isset( $data->sub )) {
        throw new Exception('Provider API returned an unexpected response.');
    }

    $this->user->profile->identifier  = @ (property_exists($data, 'sub')) ? $data->sub : '';

    $this->user->profile->displayName = @ (property_exists($data, 'name')) ? $data->name : '';
    $this->user->profile->firstName   = @ (property_exists($data, 'given_name')) ? $data->given_name : '';
    // $this->user->profile->middleName  = @ (property_exists($data, 'middle_name')) ? $data->middle_name : ''; // not supported
    $this->user->profile->lastName    = @ (property_exists($data, 'family_name')) ? $data->family_name : '';

    if (!$this->user->profile->displayName && $this->user->profile->firstName) {
        $this->user->profile->displayName = join(' ',
                                                array(
                                                  $this->user->profile->firstName,
                                                  $this->user->profile->lastName
                                                ));
    }

    $this->user->profile->email       = @ (property_exists($data, 'email')) ? $data->email : '';
    $this->user->profile->emailVerified = ! empty($data->email_verified) ? $this->user->profile->email : '';

    $this->user->profile->phone       = @ (property_exists($data, 'phone_number')) ? $data->phone_number : '';
    // $this->user->profile->phoneVerified = ! empty($data->phone_verified) ? $this->user->profile->phone : ''; // not supported

    $this->user->profile->profileURL  = @ (property_exists($data, 'profile')) ? $data->profile : '';
    $this->user->profile->webSiteURL  = @ (property_exists($data, 'website')) ? $data->website : '';
    $this->user->profile->photoURL    = @ (property_exists($data, 'picture')) ? $data->picture : '';
    $this->user->profile->gender      = @ (property_exists($data, 'gender')) ? $data->gender : '';

    if (property_exists($data, 'address'))
    {
      $address = $data->address;

      $this->user->profile->address     = @ (property_exists($address, 'street_address')) ? $address->street_address : '';
      $this->user->profile->city        = @ (property_exists($address, 'locality')) ? $address->locality : '';
      $this->user->profile->country     = @ (property_exists($address, 'country')) ? $address->country : '';
      $this->user->profile->region      = @ (property_exists($address, 'region')) ? $address->region : '';
      $this->user->profile->zip         = @ (property_exists($address, 'postal_code')) ? $address->postal_code : '';
    }

    return $this->user->profile;
  }
}
