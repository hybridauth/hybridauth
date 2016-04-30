<?php
/**
 * Hybrid_Providers_MailChimp
 *
 * https://apidocs.mailchimp.com/oauth2/
 */
class Hybrid_Providers_MailChimp extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions  
	// (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
	public $scope = "";

    private $metadata_url = "https://login.mailchimp.com/oauth2/metadata";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
        $this->api->authorize_url   = "https://login.mailchimp.com/oauth2/authorize";
		$this->api->token_url       = "https://login.mailchimp.com/oauth2/token";
	}

    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile()
    {
        $this->api->api_base_url = $this->getEndpoint().'/2.0/';

        $data = $this->api->api( "users/profile.json?apikey=".$this->api->access_token );

        if ( ! isset( $data->global_user_id ) ){
            throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
        }

        $this->user->profile->identifier    = @ $data->global_user_id;
        $this->user->profile->username      = @ $data->username;
        $this->user->profile->displayName   = @ $data->name;
        $this->user->profile->email         = @ $data->email;
        $this->user->profile->photoURL      = @ $data->avatar;

        $data = $this->api->api( "helper/account-details.json?apikey=".$this->api->access_token );

        $this->user->profile->webSiteURL    = @ $data->contact->url;
        $this->user->profile->firstName     = @ $data->contact->fname;
        $this->user->profile->lastName      = @ $data->contact->lname;
        $this->user->profile->address       = @ $data->contact->address1;
        if(isset($data->contact->address2) && strlen($data->contact->address2)){
            $this->user->profile->address .= ', '.$data->contact->address2;
        }
        $this->user->profile->zip           = @ $data->contact->zip;
        $this->user->profile->city          = @ $data->contact->city;
        $this->user->profile->country       = @ $data->contact->country;
        $this->user->profile->phone         = @ $data->contact->phone;

        return $this->user->profile;
    }

    /**
     * Load the metadata, which includes the URL of the API endpoint.
     */
    function getEndpoint()
    {
        $this->api->curl_header = array(
            'Authorization: OAuth '.$this->api->access_token,
        );
        $data = $this->api->get( $this->metadata_url );

        if ( ! isset( $data->api_endpoint ) ){
            throw new Exception( "Endpoint request failed! {$this->providerId} returned an invalid response.", 6 );
        }

        return $data->api_endpoint;
    }
}
