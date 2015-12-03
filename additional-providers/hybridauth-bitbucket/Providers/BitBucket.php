<?php
/**
 * HybridAuth
 * http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
 * (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
 */

/**
 * BitBucket Provider for HybridAuth
 * @package GitLauncher\HybridAuth\Providers
 * @author Gabriel Somoza <contact@gabrielsomoza.com>
 * @coauthor Filippo "Shade" <legend_k@live.it>
 */
class Hybrid_Providers_BitBucket extends Hybrid_Provider_Model_OAuth2
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
        $this->api->api_base_url      = "https://api.bitbucket.org/2.0/";
        $this->api->authorize_url     = "https://bitbucket.org/site/oauth2/authorize";
        $this->api->token_url         = "https://bitbucket.org/site/oauth2/access_token";
    }
    /**
     * load the user profile from the IDp api client
     */
    function getUserProfile()
    {

        try {
        
            $response = $this->api->get( 'user' );
            
            $this->user->profile->identifier    = @$response->uuid;
            $this->user->profile->username      = @$response->username;
            $this->user->profile->displayName   = @$response->display_name;
            
            // Removing the last "/" char from the avatar link and adding a 0 ensures the maximum size for the avatar
            $this->user->profile->photoURL      = rtrim(@$response->links->avatar->href, '/') . "0";
            $this->user->profile->webSiteURL    = @$response->website;
            $this->user->profile->region        = @$response->location;

            if (!$this->user->profile->displayName) {
                $this->user->profile->displayName = @$response->username;
            }
            
        } catch(\Exception $e) {
            throw new \Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
        }

        // request user emails from BitBucket api
        try {
        
            $username = $this->user->profile->username;

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
