<?php

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Linkedin OAuth2 Class
 *
 * @package             HybridAuth providers package
 * @author              Kimball Bighorse <kbighorse@yahoo.com>
 * @version             0.1
 * @license             BSD License
 */

/**
 * Hybrid_Providers_Linkedin - Linkedin provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_LinkedIn extends Hybrid_Provider_Model_OAuth2 {
    /**
     * Adapter initializer
     */
    function initialize() {
        if (!$this->config["keys"]["key"] || !$this->config["keys"]["secret"]) {
            throw new Exception("Your application key and secret are required in order to connect to {$this->providerId}.", 4);
        }

        // include OAuth2 client and Paypal client
        require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth2Client.php";

        // create a new OAuth2 client instance
        $this->api = new OAuth2Client($this->config["keys"]["key"],
                                        $this->config["keys"]["secret"],
                                        $this->endpoint);

        // If we have an access token, set it
        if($this->token("access_token")) {
            $this->api->access_token            = $this->token("access_token");
            $this->api->refresh_token           = $this->token("refresh_token");
            $this->api->access_token_expires_in = $this->token("expires_in");
            $this->api->access_token_expires_at = $this->token("expires_at");
        }

        // Provider api end-points
        $this->api->authorize_url  = "https://www.linkedin.com/oauth/v2/authorization";
        $this->api->token_url      = "https://www.linkedin.com/oauth/v2/accessToken";
        $this->api->token_info_url = "https://www.linkedin.com/oauth/v2";
    }

    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile() {
        $fields = array(
            'email-address',
            'id',
            'first-name',
            'last-name',
            'picture-url',
            'public-profile-url',
            'summary',
            'formatted-name',
            'positions'
            );
        $this->api->curl_header = array(
            'Connection: Keep-Alive',
            'Authorization: Bearer ' . $this->api->access_token,
        );

        // refresh tokens if needed
        $this->refreshToken();

        // ask linkedin api for user info
        $base_url = "https://api.linkedin.com/v1/people/~:";
        $url = $base_url . "(" . implode(",", $fields) . ")";
        $params = array('format' => 'json');

        try {
            $data = $this->api->api($url, "GET", $params, true);
        } catch (Exception $e) {
            throw new Exception("User profile request failed! {$this->providerId} returned an error: {$e->getMessage()}", 6, $e);
        }

        $this->user->profile->email         = $data->emailAddress;
        $this->user->profile->emailVerified = $data->emailAddress;
        $this->user->profile->identifier    = $data->id;
        $this->user->profile->firstName     = $data->firstName;
        $this->user->profile->lastName      = $data->lastName;
        $this->user->profile->profileURL    = $data->publicProfileUrl;
        $this->user->profile->description   = $data->summary;
        $this->user->profile->displayName   = $data->formattedName;

        if ($data->positions) {
            $this->user->profile->job_title = $data->positions->values[0]->title;
            $this->user->profile->organization_name = $data->positions->values[0]->company->name;
        }
        if (isset($data->pictureUrl)) {
            $this->user->profile->photoURL = $data->pictureUrl;
        } else {
            $this->user->profile->photoURL = "";
        }

        return $this->user->profile;
    }
}
