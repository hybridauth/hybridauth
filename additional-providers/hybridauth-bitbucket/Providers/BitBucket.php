<?php
/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
 * (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
 */

/**
 * BitBucket Provider for HybridAuth
 * @package GitLauncher\HybridAuth\Providers
 * @author Gabriel Somoza <contact@gabrielsomoza.com>
 */
class Hybrid_Providers_BitBucket extends Hybrid_Provider_Model_OAuth1
{

    // default permissions
    // (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
    public $scope = '';

    /**
     * IDp wrappers initializer
     */
    function initialize()
    {
        parent::initialize();
        // provider api end-points
        $this->api->api_base_url      = "https://bitbucket.org/api/1.0/";
        $this->api->authorize_url     = "https://bitbucket.org/api/1.0/oauth/authenticate";
        $this->api->request_token_url = "https://bitbucket.org/api/1.0/oauth/request_token";
        $this->api->access_token_url  = "https://bitbucket.org/api/1.0/oauth/access_token";
        $this->api->curl_auth_header  = false;
    }
    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile()
    {

        try{
            $response = $this->api->get( 'user' );
            $this->user->profile->identifier    = @$response->user->username;
            $this->user->profile->displayName   = @$response->user->display_name;
            $this->user->profile->firstName     = @$response->user->first_name;
            $this->user->profile->lastName      = @$response->user->last_name;
            $this->user->profile->photoURL      = @$response->user->avatar;

            if( ! $this->user->profile->displayName ){
                $this->user->profile->displayName = @$response->username;
            }
        } catch( \Exception $e ){
            throw new \Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
        }

        // request user emails from BitBucket api
        try {
            $username = $this->user->profile->identifier;

            $emails = $this->api->api("users/$username/emails");
            foreach ($emails as $email) {
                if ($email->primary) {
                    $this->user->profile->email = $email->email;
                    $this->user->profile->emailVerified = (bool)$email->active;
                    break;
                }
            }
            // if no primary email found for some reason, fall back to using the first email or fail gracefully
            if (!$this->user->profile->email && is_array($emails) && !empty($emails[0])) {
                $this->user->profile->email = $emails[0]->email;
            }
        } catch (\Exception $e) {
            throw new \Exception("User email request failed! {$this->providerId} returned an error: $e", 6);
        }

        return $this->user->profile;
    }
}
