<?php

/* !
 * Hybridauth
 * https://hybridauth.github.io/hybridauth | https://github.com/hybridauth/hybridauth
 * (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

/**
 * Hybrid_Providers_LinkedIn OAuth2 provider adapter.
 */
class Hybrid_Providers_LinkedIn extends Hybrid_Provider_Model_OAuth2 {

    /**
     * {@inheritdoc}
     */
    public $scope = "r_liteprofile r_emailaddress";

    /**
     * {@inheritdoc}
     */
    function initialize() {
        parent::initialize();

        // Provider api end-points.
        $this->api->api_base_url = "https://api.linkedin.com/v2/";
        $this->api->authorize_url = "https://www.linkedin.com/oauth/v2/authorization";
        $this->api->token_url = "https://www.linkedin.com/oauth/v2/accessToken";
    }

    /**
     * {@inheritdoc}
     */
    function loginBegin() {
        if (is_array($this->scope)) {
            $this->scope = implode(" ", $this->scope);
        }
        if (isset($this->scope)) {
            $extra_params['scope'] = $this->scope;
        }
        if (!isset($this->state)) {
            $this->state = hash("sha256",(uniqid(rand(), TRUE)));
        }
        $extra_params['state'] = $this->state;
        Hybrid_Auth::redirect($this->api->authorizeUrl($extra_params));
    }

    /**
     * {@inheritdoc}
     *
     * Get user profile fields.
     *
     * @see https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/sign-in-with-linkedin?context=linkedin/consumer/context
     */
    function getUserProfile() {
        // Refresh tokens if needed.
        $this->setHeaders("token");
        $this->refreshToken();

        // https://developer.linkedin.com/docs/fields.
        $fields = isset($this->config["fields"]) ? $this->config["fields"] : array(
            "id",
            "firstName",
            "lastName",
            "profilePicture(displayImage~:playableStreams)",
        );

        $this->setHeaders();
        $response = $this->getLinkedinResponse(
            "me?projection=(" . implode(",", $fields) . ")"
        );

        if (!isset($response->id)) {
            throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: " . Hybrid_Logger::dumpData($response), 6);
        }

        $this->user->profile->identifier = isset($response->id) ? $response->id : "";
        $this->user->profile->firstName = isset($response->firstName) ? $response->firstName : "";
        $this->user->profile->lastName = isset($response->lastName) ? $response->lastName : "";
        $this->user->profile->email = $this->getUserEmail();
        $this->user->profile->emailVerified = $this->user->profile->email;

        $first_name_lang_code = $response->firstName->preferredLocale->language . '_' . $response->firstName->preferredLocale->country;
        if (!empty($response->firstName->localized->$first_name_lang_code)) {
            $this->user->profile->firstName = $response->firstName->localized->$first_name_lang_code;
        }

        $last_name_lang_code = $response->lastName->preferredLocale->language . '_' . $response->lastName->preferredLocale->country;
        if (!empty($response->lastName->localized->$last_name_lang_code)) {
            $this->user->profile->lastName = $response->lastName->localized->$last_name_lang_code;
        }

        $this->user->profile->displayName = trim($this->user->profile->firstName . " " . $this->user->profile->lastName);

        if (!empty($response->profilePicture->{'displayImage~'}->elements)) {
            $profilePictures = $response->profilePicture->{'displayImage~'}->elements;
            foreach ($profilePictures as $profilePicture) {
                if (!empty($profilePicture->data->{'com.linkedin.digitalmedia.mediaartifact.StillImage'}->displaySize->width)) {
                    // Take last item, that should contain image in highest resolution.
                    $this->user->profile->photoURL = $profilePicture->identifiers[0]->identifier;
                }
            }
        }

        return $this->user->profile;
    }

    /**
     * {@inheritdoc}
     *
     * Get user email address in separate request.
     *
     * @see https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/sign-in-with-linkedin?context=linkedin/consumer/context
     **/
    function getUserEmail() {
        // Refresh tokens if needed.
        $this->setHeaders("token");
        $this->refreshToken();

        $this->setHeaders();

        // See: https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/sign-in-with-linkedin?context=linkedin/consumer/context
        $response = $this->getLinkedinResponse(
            'emailAddress?q=members&projection=(elements*(handle~))'
        );

        if (empty($response->elements[0]->{'handle~'}->emailAddress)) {
            throw new Exception("User email request failed! {$this->providerId} returned an invalid response: " . Hybrid_Logger::dumpData($response), 6);
        }

        $email = isset($response->elements[0]->{'handle~'}->emailAddress) ? $response->elements[0]->{'handle~'}->emailAddress : "";

        return $email;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $status
     *   An associative array containing:
     *   - content: A collection of fields describing the shared content.
     *   - comment: A comment by the member to associated with the share.
     *   - visibility: A collection of visibility information about the share.
     * @param string $companyId (optional) User company id
     *
     * @return object
     *   An object containing:
     *   - updateKey - A unique ID for the shared content posting that was just created.
     *   - updateUrl - A direct link to the newly shared content on LinkedIn.com that you can direct the user's web browser to.
     * @throws Exception
     * @see https://developer.linkedin.com/docs/share-on-linkedin
     */
    function setUserStatus($status, $companyId = null) {
        // Refresh tokens if needed.
        $this->setHeaders("token");
        $this->refreshToken();

        try {
            // Define default visibility.
            if (!isset($status["visibility"])) {
                $status["visibility"]["code"] = "anyone";
            }

            $this->setHeaders("share");
            $url = $companyId ? "companies/{$companyId}/shares?format=json" : "people/~/shares?format=json";
            $response = $this->api->post($url,
                array(
                    "body" => $status,
                )
            );
        } catch (Exception $e) {
            throw new Exception("Update user status failed! {$this->providerId} returned an error: {$e->getMessage()}", 0, $e);
        }

        if (!isset($response->updateKey)) {
            throw new Exception("Update user status failed! {$this->providerId} returned an error: {$response->message}", $response->errorCode);
        }

        return $response;
    }

    /**
     * Set correct request headers.
     *
     * @param string $api_type
     *   (optional) Specify api type.
     *
     * @return void
     */
    private function setHeaders($api_type = null) {
        $this->api->curl_header = array(
            "Authorization: Bearer {$this->api->access_token}",
        );

        switch ($api_type) {
            case "share":
                $this->api->curl_header = array_merge(
                    $this->api->curl_header,
                    array(
                        "Content-Type: application/json",
                        "x-li-format: json",
                    )
                );
                break;

            case "token":
                $this->api->curl_header = array_merge(
                    $this->api->curl_header,
                    array(
                        "Content-Type: application/x-www-form-urlencoded",
                    )
                );
                break;
        }
    }

    /**
     * Format and sign an oauth for provider api for LinkedIn.
     *
     * Don't use the access token for LinkedIn.
     */
    protected function getLinkedinResponse($url, $method = "GET", $parameters = array(), $decode_json = true )
    {
        if ( strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0 ) {
            $url = $this->api->api_base_url . $url;
        }

        $response = null;

        switch( $method ){
            case 'GET'  : $response = $this->api->request( $url, $parameters, "GET"  ); break;
            case 'POST' : $response = $this->api->request( $url, $parameters, "POST" ); break;
            case 'DELETE' : $response = $this->api->request( $url, $parameters, "DELETE" ); break;
            case 'PATCH'  : $response = $this->api->request( $url, $parameters, "PATCH" ); break;
        }

        if( $response && $decode_json ){
            return $this->api->response = json_decode( $response );
        }

        return $this->api->response = $response;
    }

}
