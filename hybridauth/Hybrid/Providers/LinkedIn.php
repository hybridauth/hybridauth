<?php

/*!
 * Hybridauth
 * https://hybridauth.github.io/hybridauth | https://github.com/hybridauth/hybridauth
 *  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

/**
 * Hybrid_Providers_LinkedIn OAuth2 provider adapter.
 */
class Hybrid_Providers_LinkedIn extends Hybrid_Provider_Model_OAuth2 {

    /**
     * {@inheritdoc}
     */
    public $scope = 'r_liteprofile r_emailaddress w_member_social';

    /**
     * The 'state' variable helps to prevent CSRF attacks,
     * and can also be used to identify the authentication request.
     */
    protected $state = NULL;

    /**
     * {@inheritdoc}
     */
    public function initialize() {
        parent::initialize();

        // Provider api end-points.
        $this->api->api_base_url = "https://api.linkedin.com/v2/";
        $this->api->authorize_url = "https://www.linkedin.com/oauth/v2/authorization";
        $this->api->token_url = "https://www.linkedin.com/oauth/v2/accessToken";

        if ($this->api->access_token) {
            $this->api->curl_header[] = 'Authorization: Bearer ' . $this->api->access_token;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loginBegin() {
        if (is_array($this->scope)) {
            $this->scope = implode(" ", $this->scope);
        }
        if (!isset($this->state)) {
            $this->state = hash("sha256",(uniqid(rand(), TRUE)));
        }

        $extra_params = [
          'scope' => $this->scope,
          'state' => $this->state,
        ];
        Hybrid_Auth::redirect($this->api->authorizeUrl($extra_params));
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile() {
        $this->refreshToken();

        $fields = [
          'id',
          'firstName',
          'lastName',
          'profilePicture(displayImage~:playableStreams)',
        ];

        $response = $this->api->get('me?projection=(' . implode(',', $fields) . ')', [], false);
        $response = $response ? json_decode($response, true) : [];

        if (empty($response['id'])) {
            throw new Exception($response['message'], 6);
        }

        // Handle localized names.
        $locale = $this->getPreferredLocale($response, 'firstName');
        $this->user->profile->firstName = isset($response['firstName']['localized'][$locale]) ?
          $response['firstName']['localized'][$locale] : '';

        $locale = $this->getPreferredLocale($response, 'lastName');
        $this->user->profile->lastName = isset($response['lastName']['localized'][$locale]) ?
          $response['lastName']['localized'][$locale] : '';

        // Handle amazing profile picture structure.
        $this->user->profile->photoURL = !empty($response['profilePicture']['displayImage~']['elements']) ?
          $this->getUserPhotoUrl($response['profilePicture']['displayImage~']['elements']) : '';

        // Handle other details.
        $this->user->profile->identifier = $response['id'];
        $this->user->profile->email = $this->getUserEmail();
        $this->user->profile->emailVerified = $this->user->profile->email;
        $this->user->profile->displayName = trim($this->user->profile->firstName . " " . $this->user->profile->lastName);

        return $this->user->profile;
    }

    /**
     * Returns a user photo.
     *
     * @param array $elements
     *   List of file identifiers related to this artifact.
     *
     * @return string
     *   The user photo URL.
     *
     * @see https://docs.microsoft.com/en-us/linkedin/shared/references/v2/profile/profile-picture
     */
    public function getUserPhotoUrl($elements)
    {
        if (is_array($elements)) {
            // Get the largest picture from the list which is the last one.
            $element = end($elements);
            if (!empty($element['identifiers'])) {
                return $element['identifiers'][0]['identifier'];
            }
        }

        return null;
    }

    /**
     * Returns an email address of user.
     *
     * @return string
     *   The user email address.
     *
     * @throws \Exception
     */
    public function getUserEmail()
    {
        $this->refreshToken();
        $response = $this->api->get('emailAddress?q=members&projection=(elements*(handle~))', [], false);
        $response = $response ? json_decode($response, true) : [];

        if (empty($response['elements'])) {
            throw new Exception($response['message'], 6);
        }

        foreach ($response['elements'] as $element) {
            if (isset($element['handle~']['emailAddress'])) {
                return $element['handle~']['emailAddress'];
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.microsoft.com/en-us/linkedin/consumer/integrations/self-serve/share-on-linkedin
     */
    public function setUserStatus($status, $userID = null)
    {
        $this->refreshToken();
        if (is_string($status)) {
            $status = [
              'author' => 'urn:li:person:' . $userID,
              'lifecycleState' => 'PUBLISHED',
              'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                  'shareCommentary' => [
                    'text' => $status,
                  ],
                  'shareMediaCategory' => 'NONE',
                ],
              ],
              'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
              ],
            ];
        }

        // Set a new headers for POST request and back to original ones
        // when request is done.
        $curl_header = $this->api->curl_header;
        $this->api->curl_header[] = 'Content-Type: application/json';
        $this->api->curl_header[] = 'x-li-format: json';
        $this->api->curl_header[] = 'X-Restli-Protocol-Version: 2.0.0';

        $response = $this->api->post("ugcPosts", ['body' => $status], false);
        $response = $response ? json_decode($response, true) : [];
        $this->api->curl_header = $curl_header;

        if (empty($response['id'])) {
            throw new Exception($response['message'], 6);
        }

        return $response['id'];
    }

    /**
     * Returns a preferred locale for given field.
     *
     * @param array $data
     *   A data to check.
     * @param string $field_name
     *   A field name to perform.
     *
     * @return string
     *   A field locale.
     */
    protected function getPreferredLocale($data, $field_name)
    {
        if (!empty($data[$field_name]['preferredLocale'])) {
            $locale = $data[$field_name]['preferredLocale'];

            return $locale['language'] . '_' . $locale['country'];
        }

        return 'en_US';
    }
}
