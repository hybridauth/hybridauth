<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Citrix
 */
class Hybrid_Providers_Citrix extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions  
	// (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
	public $scope = "";

    private $userAccounts;

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.citrixonline.com/";
		$this->api->authorize_url = "https://api.citrixonline.com/oauth/authorize";
		$this->api->token_url     = "https://api.citrixonline.com/oauth/access_token";

        $this->api->curl_authenticate_method  = "GET";
	}

    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile()
    {
        $this->api->curl_header = array(
            'Authorization: OAuth oauth_token='.$this->api->access_token,
        );
        $data = $this->api->get( "admin/rest/v1/me" );

        if ( ! isset( $data->key ) ){
            throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
        }

        $this->user->profile->identifier  = @ $data->key;
        $this->user->profile->firstName = @ $data->firstName;
        $this->user->profile->firstName = @ $data->firstName;
        $this->user->profile->displayName = @ $data->firstName.' '.$data->lastName;
        $this->user->profile->email = @ $data->email;

        $this->userAccounts = $data->accounts;

        return $this->user->profile;
    }

    /**
     * Returns the accounts owned by the Citrix user.
     *
     * @return mixed
     */
    function getUserAccounts()
    {
        return $this->userAccounts;
    }
}