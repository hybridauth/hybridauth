<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Justia
 */
class Hybrid_Providers_Justia extends Hybrid_Provider_Model_OAuth2 {
    // default permissions
//    public $scope = "basic,email,read_profiles";
    public $scope = "basic";

    /**
     * IDp wrappers initializer
     */
    function initialize() {
        parent::initialize();

        // Provider api end-points
        $this->api->api_base_url  = "https://accounts.justia.com/api/v1.0/me";
        $this->api->authorize_url = "https://accounts.justia.com/oauth/authorize";
        $this->api->token_url     = "https://accounts.justia.com/oauth/access_token";
    }

    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile() {
        $this->api->curl_header = array(
            'Response-Type: json',
            'Connection: Keep-Alive',
            'Authorization: Bearer ' . $this->api->access_token,
        );
        $data = $this->api->api( "user" );

        if (!isset($data->id)) {
            throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6);
        }

        $this->user->profile->identifier  = @ $data->uid;
        $this->user->profile->displayName = @ $data->name;
        $this->user->profile->firstName   = @ $data->firstname;
        $this->user->profile->lastName    = @ $data->lastname;
        $this->user->profile->email       = @ $data->email;
        $this->user->profile->description = @ $data->bio;

        if(empty($this->user->profile->displayName)) {
            $this->user->profile->displayName = (@ $data->firstname) . " " . @ $data->lastname;
        }

        return $this->user->profile;
    }
}
