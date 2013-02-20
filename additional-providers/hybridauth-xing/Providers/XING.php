<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * XING.com Provider
 *
 * @author  Fabian Beiner <mail@fabian-beiner.de>
 * @version 1.0.1
 */
class Hybrid_Providers_XING extends Hybrid_Provider_Model_OAuth1 {
    /**
     * Initialize.
     */
    function initialize() {
        if (!$this->config['keys']['key'] || !$this->config['keys']['secret']) {
            throw new Exception('You need a consumer key and secret to connect to ' . $this->providerId . '.');
        }

        parent::initialize();

        // XING API endpoints.
        $this->api->api_base_url      = 'https://api.xing.com/v1/';
        $this->api->authorize_url     = 'https://api.xing.com/v1/authorize';
        $this->api->request_token_url = 'https://api.xing.com/v1/request_token';
        $this->api->access_token_url  = 'https://api.xing.com/v1/access_token';

        // Currently there is only version "v1" available.
        if (isset($this->config['api_version']) && $this->config['api_version']) {
            $this->api->api_base_url = 'https://api.xing.com/' . $this->config['api_version'] . '/';
        }

        // We don't need them.
        $this->api->curl_auth_header = false;
    }

    /**
     * Begin logging in.
     */
    function loginBegin() {
        // Handle the request token.
        $aToken                   = $this->api->requestToken($this->endpoint);
        $this->request_tokens_raw = $aToken;

        // The HTTP status code needs to be 201. If it's not, something is wrong.
        if ($this->api->http_code !== 201) {
            throw new Exception('Authentication failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus($this->api->http_code) . '.');
        }

        // If we don't have an OAuth token by now, something is ABSOLUTELY wrong.
        if (!isset($aToken['oauth_token'])) {
            throw new Exception('Authentication failed! ' . $this->providerId . ' returned an invalid OAuth token.');
        }

        $this->token('request_token'       , $aToken['oauth_token']);
        $this->token('request_token_secret', $aToken['oauth_token_secret']);

        // Redirect to the XING authorization URL.
        Hybrid_Auth::redirect($this->api->authorizeUrl($aToken));
    }

    /**
     * Finish logging in.
     */
    function loginFinish() {
        $sToken    = (isset($_REQUEST['oauth_token'])) ? $_REQUEST['oauth_token'] : '';
        $sVerifier = (isset($_REQUEST['oauth_verifier'])) ? $_REQUEST['oauth_verifier'] : '';

        if (!$sToken || !$sVerifier) {
            throw new Exception('Authentication failed! ' . $this->providerId . ' returned an invalid OAuth token/verifier.');
        }

        // Handle the access token.
        $aToken                  = $this->api->accessToken($sVerifier);
        $this->access_tokens_raw = $aToken;

        // You know the deal, don't you? :)
        if ($this->api->http_code !== 201) {
            throw new Exception('Authentication failed! ' . $this->providerId . ' returned an error: ' . $this->errorMessageByStatus($this->api->http_code) . '.');
        }

        // If we don't have an OAuth token by now, something is ABSOLUTELY wrong.
        if (!isset($aToken['oauth_token'])) {
            throw new Exception('Authentication failed! ' . $this->providerId . ' returned an invalid OAuth token.');
        }

        // Delete the request tokens, as we don't need them anymore.
        $this->deleteToken('request_token');
        $this->deleteToken('request_token_secret');

        // But store the access tokens for later usage.
        $this->token('access_token',        $aToken['oauth_token']);
        $this->token('access_token_secret', $aToken['oauth_token_secret']);

        // Connection established!
        $this->setUserConnected();
    }

    /**
    * Gets the profile of the user who has granted access.
    *
    * @see https://dev.xing.com/docs/get/users/me
    */
    function getUserProfile() {
        $oResponse = $this->api->get('users/me');

        // The HTTP status code needs to be 200 here. If it's not, something is wrong.
        if ($this->api->http_code !== 200) {
            throw new Exception('Profile request failed! ' . $this->providerId . ' API returned an error: ' . $this->errorMessageByStatus($this->api->http_code) . '.');
        }

        // We should have an object by now.
        if (!is_object($oResponse)) {
            throw new Exception('Profile request failed! ' . $this->providerId . ' API returned an error: invalid response.');
        }

        // Redefine the object.
        $oResponse = $oResponse->users[0];

        /**
         * Handle the profile data.
         *
         * @see  http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Profile.html
         */
        $this->user->profile->identifier    = (property_exists($oResponse, 'id'))           ? $oResponse->id           : '';
        $this->user->profile->profileURL    = (property_exists($oResponse, 'permalink'))    ? $oResponse->permalink    : '';
        $this->user->profile->displayName   = (property_exists($oResponse, 'display_name')) ? $oResponse->display_name : '';
        $this->user->profile->description   = (property_exists($oResponse, 'interests'))    ? $oResponse->interests    : ''; // Not really a "description, but anyways …
        $this->user->profile->firstName     = (property_exists($oResponse, 'first_name'))   ? $oResponse->first_name   : '';
        $this->user->profile->lastName      = (property_exists($oResponse, 'last_name'))    ? $oResponse->last_name    : '';
        $this->user->profile->gender        = (property_exists($oResponse, 'gender'))       ? $oResponse->gender       : '';
        $this->user->profile->emailVerified = (property_exists($oResponse, 'active_email')) ? $oResponse->active_email : '';

        // My own priority: Homepage, blog, other, something else.
        if (property_exists($oResponse, 'web_profiles')) {
            $this->user->profile->webSiteURL = (property_exists($oResponse->web_profiles, 'homepage')) ? $oResponse->web_profiles->homepage[0] : null;
            if (null === $this->user->profile->webSiteURL) {
                $this->user->profile->webSiteURL = (property_exists($oResponse->web_profiles, 'blog')) ? $oResponse->web_profiles->blog[0] : null;
            }
            if (null === $this->user->profile->webSiteURL) {
                $this->user->profile->webSiteURL = (property_exists($oResponse->web_profiles, 'other')) ? $oResponse->web_profiles->other[0] : null;
            }
            // Just use *anything*!
            if (null === $this->user->profile->webSiteURL) {
                foreach ($oResponse->web_profiles as $aUrl) {
                    $this->user->profile->webSiteURL = $aUrl[0];
                    break;
                }
            }
        }

        // We use the largest picture available.
        if (property_exists($oResponse, 'photo_urls') && property_exists($oResponse->photo_urls, 'large')) {
            $this->user->profile->photoURL = (property_exists($oResponse->photo_urls, 'large')) ? $oResponse->photo_urls->large : '';
        }

        // Try to get the native language first.
        if (property_exists($oResponse, 'languages')) {
            foreach ($oResponse->languages as $sLanguage => $sSkill) {
                $this->user->profile->language = strtoupper($sLanguage);
                if ($sSkill == 'NATIVE') {
                    break;
                }
            }
        }

        // Age stuff.
        if (property_exists($oResponse, 'birth_date')) {
            $this->user->profile->age        = floor((time() - strtotime($oResponse->birth_date->year . '-' . $oResponse->birth_date->month . '-' . $oResponse->birth_date->day)) / 31556926);
            $this->user->profile->birthDay   = $oResponse->birth_date->day;
            $this->user->profile->birthMonth = $oResponse->birth_date->month;
            $this->user->profile->birthYear  = $oResponse->birth_date->year;
        }

        // As XING is a business network, users are more likely to be interested in the business address.
        $oAddress = (property_exists($oResponse, 'business_address')) ? $oResponse->business_address : null;
        if (null === $oAddress && property_exists($oResponse, 'private_address')) {
            $oAddress = $oResponse->private_address;
        }
        if (null !== $oAddress) {
            $this->user->profile->phone   = (property_exists($oAddress, 'phone'))        ? $oAddress->phone        : '';
            $this->user->profile->address = (property_exists($oAddress, 'street'))       ? $oAddress->street       : '';
            $this->user->profile->country = (property_exists($oAddress, 'country'))      ? $oAddress->country      : '';
            $this->user->profile->region  = (property_exists($oAddress, 'province'))     ? $oAddress->province     : '';
            $this->user->profile->city    = (property_exists($oAddress, 'city'))         ? $oAddress->city         : '';
            $this->user->profile->zip     = (property_exists($oAddress, 'zip_code'))     ? $oAddress->zip_code     : '';
            $this->user->profile->email   = (property_exists($oAddress, 'email'))        ? $oAddress->email        : '';
            if (null === $this->user->profile->language) {
                $this->user->profile->language = (property_exists($oAddress, 'country')) ? $oAddress->country : '';
            }
            // The following two are actually not part of the normalized user profile structure used by HybridAuth...
            $this->user->profile->mobile  = (property_exists($oAddress, 'mobile_phone')) ? $oAddress->mobile_phone : '';
            $this->user->profile->fax     = (property_exists($oAddress, 'fax'))          ? $oAddress->fax          : '';
        }

        return $this->user->profile;
    }

    /**
     * Update the user status.
     *
     * @see http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Status.html
     */
    function setUserStatus($sMessage) {
        $aParameters = array(
            'oauth_token' => $this->token('access_token')
           ,'id'          => 'me'
        );

        // German network, there will probably be Umlauts somewhere. :)
        mb_internal_encoding('UTF-8');

        if (!is_string($sMessage) || $sMessage == '') {
            throw new Exception('The passed parameter needs to be a string.');
        }

        // Check if the message is <= 420 characters.
        if (strlen($sMessage) >= 420) {
            $aParameters['message'] = mb_substr($sMessage, 0, 419) . '…';
        }
        else {
            $aParameters['message'] = $sMessage;
        }

        try {
            $oResponse = $this->api->post('users/' . $aParameters['id'] . '/status_message', $aParameters);
            if ($this->api->http_code === 201) {
                return true;
            }
            elseif ($this->api->http_code === 403) {
                throw new Exception('Something went wrong. ' . $this->providerId . ' denied the access.');
            }
            elseif ($this->api->http_code === 404) {
                throw new Exception('The user "' . $aParameters['id'] . '" was not found.');
            }
            return false;
        }
        catch(Exception $e) {
            throw new Exception('Could not update the status. ' . $this->providerId . ' returned an error: ' . $e . '.');
        }
    }

    /**
     * Load user contacts.
     *
     * @see http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Contacts.html
     */
    function getUserContacts() {
        try {
            $oResponse = $this->api->get('users/me/contacts?limit=100&user_fields=id,display_name,permalink,web_profiles,photo_urls,display_name,interests,active_email&offset=0');
            $oTotal    = $oResponse->contacts->users;
            $iTotal    = $oResponse->contacts->total;

            for ($i = 100; $i <= $iTotal; $i = $i + 100) {
                $oResponse = $this->api->get('users/me/contacts?limit=100&user_fields=id,display_name,permalink,web_profiles,photo_urls,display_name,interests,active_email&offset=' . $i);
                $oTotal    = array_merge($oTotal, $oResponse->contacts->users);
            }
        }
        catch(Exception $e) {
            throw new Exception('Could not fetch contacts. ' . $this->providerId . ' returned an error: ' . $e . '.');
        }

        // Return empty array if there are no contacts.
        if (count($oTotal) == 0) {
            return array();
        }

        // Create the contacts array.
        $aContacts = array();
        foreach($oTotal as $aTitle) {
            $oContact = new Hybrid_User_Contact();
            $oContact->identifier  = (property_exists($aTitle, 'id'))           ? $aTitle->id           : '';
            $oContact->profileURL  = (property_exists($aTitle, 'permalink'))    ? $aTitle->permalink    : '';
            $oContact->displayName = (property_exists($aTitle, 'display_name')) ? $aTitle->display_name : '';
            $oContact->description = (property_exists($aTitle, 'interests'))    ? $aTitle->interests    : '';
            $oContact->email       = (property_exists($aTitle, 'active_email')) ? $aTitle->active_email : '';

            // My own priority: Homepage, blog, other, something else.
            if (property_exists($aTitle, 'web_profiles')) {
                $oContact->webSiteURL = (property_exists($aTitle->web_profiles, 'homepage')) ? $aTitle->web_profiles->homepage[0] : null;
                if (null === $oContact->webSiteURL) {
                    $oContact->webSiteURL = (property_exists($aTitle->web_profiles, 'blog')) ? $aTitle->web_profiles->blog[0] : null;
                }
                if (null === $oContact->webSiteURL) {
                    $oContact->webSiteURL = (property_exists($aTitle->web_profiles, 'other')) ? $aTitle->web_profiles->other[0] : null;
                }
                // Just use *anything*!
                if (null === $oContact->webSiteURL) {
                    foreach ($aTitle->web_profiles as $aUrl) {
                        $oContact->webSiteURL = $aUrl[0];
                        break;
                    }
                }
            }

            // We use the largest picture available.
            if (property_exists($aTitle, 'photo_urls') && property_exists($aTitle->photo_urls, 'large')) {
                $oContact->photoURL = (property_exists($aTitle->photo_urls, 'large')) ? $aTitle->photo_urls->large : '';
            }

            $aContacts[] = $oContact;
        }

        return $aContacts;
    }
}
