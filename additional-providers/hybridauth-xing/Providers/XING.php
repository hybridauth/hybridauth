<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
* (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * XING.com Provider
 *
 * @author  Fabian Beiner <fb@fabianbeiner.de>
 * @version 1.1.0
 */
class Hybrid_Providers_XING extends Hybrid_Provider_Model_OAuth1
{
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

        // allows to specify which picture size to retrieve
        require_once( 'XINGUserPictureSize.php' );
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

        $this->token('request_token', $aToken['oauth_token']);
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
        $this->token('access_token', $aToken['oauth_token']);
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
        $this->user->profile->identifier    = (property_exists($oResponse, 'id')) ? $oResponse->id : '';
        $this->user->profile->profileURL    = (property_exists($oResponse, 'permalink')) ? $oResponse->permalink : '';
        $this->user->profile->displayName   = (property_exists($oResponse, 'display_name')) ? $oResponse->display_name : '';
        $this->user->profile->description   = (property_exists($oResponse, 'interests')) ? $oResponse->interests : ''; // Not really a "description, but anyways …
        $this->user->profile->firstName     = (property_exists($oResponse, 'first_name')) ? $oResponse->first_name : '';
        $this->user->profile->lastName      = (property_exists($oResponse, 'last_name')) ? $oResponse->last_name : '';
        $this->user->profile->gender        = (property_exists($oResponse, 'gender')) ? $oResponse->gender : '';
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

        // We use the '192x192' picture available.
        $requestedPictureSize = XingUserPicureSize::SIZE_192X192;
        if (property_exists($oResponse, 'photo_urls') && property_exists($oResponse->photo_urls, $requestedPictureSize )) {
            $this->user->profile->photoURL = (property_exists($oResponse->photo_urls, $requestedPictureSize )) ? $oResponse->photo_urls->$requestedPictureSize  : '';
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
            $this->user->profile->phone   = (property_exists($oAddress, 'phone')) ? $oAddress->phone : '';
            $this->user->profile->address = (property_exists($oAddress, 'street')) ? $oAddress->street : '';
            $this->user->profile->country = (property_exists($oAddress, 'country')) ? $oAddress->country : '';
            $this->user->profile->region  = (property_exists($oAddress, 'province')) ? $oAddress->province : '';
            $this->user->profile->city    = (property_exists($oAddress, 'city')) ? $oAddress->city : '';
            $this->user->profile->zip     = (property_exists($oAddress, 'zip_code')) ? $oAddress->zip_code : '';
            $this->user->profile->email   = (property_exists($oAddress, 'email')) ? $oAddress->email : '';
            if (null === $this->user->profile->language) {
                $this->user->profile->language = (property_exists($oAddress, 'country')) ? $oAddress->country : '';
            }
            // The following two are actually not part of the normalized user profile structure used by HybridAuth...
            $this->user->profile->mobile = (property_exists($oAddress, 'mobile_phone')) ? $oAddress->mobile_phone : '';
            $this->user->profile->fax    = (property_exists($oAddress, 'fax')) ? $oAddress->fax : '';
        }

        return $this->user->profile;
    }

    /**
     * Update the user status.
     *
     * @see http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Status.html
     */
    function setUserStatus($sMessage) {
        $aParameters = array('oauth_token' => $this->token('access_token'),
                             'id'          => 'me');

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
        } catch (Exception $e) {
            throw new Exception('Could not update the status. ' . $this->providerId . ' returned an error: ' . $e . '.');
        }
    }

    /**
     * Load user contacts.
     *
     * @see http://hybridauth.sourceforge.net/userguide/Profile_Data_User_Contacts.html
     *
     * @param string $xingUserPicureSize the requested size of picture to return
     * @return XingUser[]
     * @throws Exception
     */
    function getUserContacts( $xingUserPicureSize = null ) {
        require_once 'XINGUser.php';
        $user_fields_string = XingUser::getApiRequestFields();

        $requestParameters = array(
            'user_fields' => $user_fields_string,
            'order_by' => 'last_name',
            'limit' => 100,
            'offset' => 0,
        );

        $requestEndpoint = 'users/me/contacts';
        $apiResponse = $this->api->get('users/me/contacts', $requestParameters);
        $this->verifyResponse($requestEndpoint, $this->api->http_code, $apiResponse);

        $apiContacts = $apiResponse->contacts->users;
        $contactsCount = $apiResponse->contacts->total;

        for ($offset = 100; $offset <= $contactsCount; $offset += 100) {
            $requestParameters['offset'] = $offset;
            $apiResponse = $this->api->get($requestEndpoint, $requestParameters);
            $apiContacts = array_merge($apiContacts, $apiResponse->contacts->users);
        }

        // Return empty array if there are no contacts.
        if (count($apiContacts) == 0) {
            return array();
        }

        // Create the contacts array.
        $myContacts = array();
        foreach ($apiContacts as $apiUser) {
            $myContacts[] = new XingUser($apiUser, $xingUserPicureSize);
        }

        return $myContacts;
    }

    /**
     * Get contact count
     *
     * @see https://dev.xing.com/docs/get/users/:user_id/contacts
     *
     * @param string $xingId The XING-ID of the user
     * @return int the number of contacts
     * @throws Exception
     */
    function getUserContactCount($xingId)
    {
        try {
            $oResponse = $this->api->get('users/'. $xingId .'/contacts?limit=0');
            // The HTTP status code needs to be 200 here. If it's not, something is wrong.
            if ($this->api->http_code !== 200) {
                throw new Exception('User Contact count request failed! ' . $this->providerId . ' API returned an error: ' . $this->errorMessageByStatus($this->api->http_code) . '.', $this->api->http_code);
            }

            // We should have an object by now.
            if (!is_object($oResponse)) {
                throw new Exception('User Contact count request failed! ' . $this->providerId . ' API returned an error: invalid response.');
            }
            return $oResponse->contacts->total;
        } catch (Exception $e) {
            throw new Exception('Could not fetch Contact count. ' . $this->providerId . ' returned an error: ' . $e . '.', $e->getCode());
        }
    }

    /**
     * Find users by given email
     *
     * @see https://dev.xing.com/docs/get/users/find_by_emails
     *
     * @param array $emails the list of emails that will be searched in XING
     * @param string $xingUserPicureSize the requested size of picture to return
     * @param boolean $isUserExisting collect only user that have an accessible XING profile
     * @return array [string email, XingUser] the associative array with emails as key
     * @throws Exception
     */
    public function findUsersByEmail( $emails, $xingUserPicureSize = null, $isUserExisting = true )
    {
        require_once 'XINGUser.php';
        $user_fields_string = XingUser::getApiRequestFields();

        $aParameters = array(
            'user_fields' => $user_fields_string,
        );

        $found_users = array();
        //each email search request has a limit of 100 emails
        $all_emails_chunks = array_chunk( $emails, 100 );
        $requestEndpoint = 'users/find_by_emails';
        foreach ($all_emails_chunks as $single_emails_chunk) {
            $aParameters[ 'emails' ] = implode( ',', $single_emails_chunk );
            $oResponse = $this->api->get( $requestEndpoint, $aParameters );
            $this->verifyResponse( $requestEndpoint, $this->api->http_code, $oResponse );

            // parse response
            foreach ($oResponse->results->items as $item) {
                $user_email = $item->email;
                $user = array();
                if (null !== $item->user) {
                    // valid user
                    if (property_exists( $item->user, 'id' ) && $item->user->id != null) {
                        // if id is null then the user is inactive or something wrong anyway
                        $user = new XingUser( $item->user, $xingUserPicureSize );
                    }
                }

                // filter only found users if requested
                if (!$isUserExisting || ( $isUserExisting && ( count( $user ) > 0 ) )) {
                    $found_users[ $user_email ] = $user;
                }
            }
        }

        return $found_users;
    }

    /**
     * Find jobs by a given criteria
     *
     * @see https://dev.xing.com/docs/get/jobs/find
     *
     * @param string $query the search query
     * @param int $limit Restrict the number of job postings to be returned. This must be a positive number. Default: 10
     * @param XingJobLocation $location A geo coordinate in the format latitude, longitude, radius. Radius is specified in kilometers. Example: “51.1084,13.6737,100”
     * @param int $offset used for paginating results
     * @return [XingJob[], jobs found count] the associative array with jobs and job-id as key and the total of jobs found for the query
     *         [can be used for pagination the results]
     * @throws Exception
     */

    //todo still to implement limit and location processing
    public function findJobsByQuery( $query, /*$limit = 10,  XingJobLocation $location = null,*/ $offset = 0 )
    {
        if (!isset( $query ) || empty( $query )) {
            throw new Exception( 'A query is required for Job Searching' );
        }

        require_once 'XINGJob.php';

        $aParameters = array(
            'query' => $query,
            'offset' => $offset,
        );

        $requestEndpoint = 'jobs/find';
        $found_jobs = array();
        $oResponse = $this->api->get( $requestEndpoint, $aParameters );
        $this->verifyResponse( $requestEndpoint, $this->api->http_code, $oResponse );

        // parse response
        $found_jobs_count = $oResponse->jobs->total;
        foreach ($oResponse->jobs->items as $item) {
            $job_id = $item->id;
            $job = new XingJob( $item );
            $found_jobs[ $job_id ] = $job;
        }

        return array( $found_jobs, $found_jobs_count );
    }

    private function verifyResponse( $requestName, $http_code, $oResponse )
    {
        // The HTTP status code needs to be 200 here. Otherwise something is wrong.
        if ($this->api->http_code !== 200) {
            throw new Exception(
                $requestName . ' request failed! ' . $this->providerId . ' API returned an error: ' . $this->errorMessageByStatus( $http_code ) . '.'
            );
        }

        // We should have an object by now.
        if (!is_object( $oResponse )) {
            throw new Exception( $requestName . ' request failed! ' . $this->providerId . ' API returned an error: invalid response.' );
        }

        return true;
    }
}
