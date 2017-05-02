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
    public $scope = "r_basicprofile r_emailaddress";

    /**
     * {@inheritdoc}
     */
    function initialize() {
        parent::initialize();

        // Provider api end-points.
        $this->api->api_base_url = "https://api.linkedin.com/v1/";
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
        parent::loginBegin();
    }

    /**
     * {@inheritdoc}
     *
     * @see https://developer.linkedin.com/docs/rest-api
     */
    function getUserProfile() {
        // Refresh tokens if needed.
        $this->setHeaders("token");
        $this->refreshToken();

        // https://developer.linkedin.com/docs/fields.
        $fields = isset($this->config["fields"]) ? $this->config["fields"] : [
            "id",
            "email-address",
            "first-name",
            "last-name",
            "headline",
            "location",
            "industry",
            "picture-url",
            "public-profile-url",
        ];

        $this->setHeaders();
        $response = $this->api->get(
            "people/~:(" . implode(",", $fields) . ")",
            array(
                "format" => "json",
            )
        );

        if (!isset($response->id)) {
            throw new Exception("User profile request failed! {$this->providerId} returned an invalid response: " . Hybrid_Logger::dumpData($response), 6);
        }

        $this->user->profile->identifier = isset($response->id) ? $response->id : "";
        $this->user->profile->firstName = isset($response->firstName) ? $response->firstName : "";
        $this->user->profile->lastName = isset($response->lastName) ? $response->lastName : "";
        $this->user->profile->photoURL = isset($response->pictureUrl) ? $response->pictureUrl : "";
        $this->user->profile->profileURL = isset($response->publicProfileUrl) ? $response->publicProfileUrl : "";
        $this->user->profile->email = isset($response->emailAddress) ? $response->emailAddress : "";
        $this->user->profile->description = isset($response->headline) ? $response->headline : "";
        $this->user->profile->country = isset($response->location) ? $response->location->name : "";
        $this->user->profile->emailVerified = $this->user->profile->email;
        $this->user->profile->displayName = trim($this->user->profile->firstName . " " . $this->user->profile->lastName);

        return $this->user->profile;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $status
     *   An associative array containing:
     *   - content: A collection of fields describing the shared content.
     *   - comment: A comment by the member to associated with the share.
     *   - visibility: A collection of visibility information about the share.
     *
     * @return object
     *   An object containing:
     *   - updateKey - A unique ID for the shared content posting that was just created.
     *   - updateUrl - A direct link to the newly shared content on LinkedIn.com that you can direct the user's web browser to.
     * @throws Exception
     * @see https://developer.linkedin.com/docs/share-on-linkedin
     */
    function setUserStatus($status) {
        // Refresh tokens if needed.
        $this->setHeaders("token");
        $this->refreshToken();

        try {
            // Define default visibility.
            if (!isset($status["visibility"])) {
                $status["visibility"]["code"] = "anyone";
            }

            $this->setHeaders("share");
            $response = $this->api->post(
                "people/~/shares?format=json",
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

}
