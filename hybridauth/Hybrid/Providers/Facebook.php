<?php

use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook as FacebookSDK;

/* !
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

/**
 * Hybrid_Providers_Facebook provider adapter based on OAuth2 protocol
 * Hybrid_Providers_Facebook use the Facebook PHP SDK created by Facebook
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html
 */
class Hybrid_Providers_Facebook extends Hybrid_Provider_Model {

    /**
     * Default permissions, and a lot of them. You can change them from the configuration by setting the scope to what you want/need.
     * For a complete list see: https://developers.facebook.com/docs/facebook-login/permissions
     *
     * @link https://developers.facebook.com/docs/facebook-login/permissions
     * @var array $scope
     */
    public $scope = ['email', 'user_about_me', 'user_birthday', 'user_hometown', 'user_location', 'user_website', 'publish_actions', 'read_custom_friendlists'];

    /**
     * Provider API client
     *
     * @var \Facebook\Facebook
     */
    public $api;

    public $useSafeUrls = true;

    /**
     * {@inheritdoc}
     */
    function initialize() {
        if (!$this->config["keys"]["id"] || !$this->config["keys"]["secret"]) {
            throw new Exception("Your application id and secret are required in order to connect to {$this->providerId}.", 4);
        }

        if (isset($this->config['scope'])) {
            $scope = $this->config['scope'];
            if (is_string($scope)) {
                $scope = explode(",", $scope);
            }
            $scope = array_map('trim', $scope);
            $this->scope = $scope;
        }

        $trustForwarded = isset($this->config['trustForwarded']) ? (bool)$this->config['trustForwarded'] : false;

        $this->api = new FacebookSDK([
            'app_id' => $this->config["keys"]["id"],
            'app_secret' => $this->config["keys"]["secret"],
            'default_graph_version' => 'v2.8',
            'trustForwarded' => $trustForwarded,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    function loginBegin() {

        $this->endpoint = $this->params['login_done'];
        $helper = $this->api->getRedirectLoginHelper();

        // Use re-request, because this will trigger permissions window if not all permissions are granted.
        $url = $helper->getReRequestUrl($this->endpoint, $this->scope);

        // Redirect to Facebook
        Hybrid_Auth::redirect($url);
    }

    /**
     * {@inheritdoc}
     */
    function loginFinish() {

        $helper = $this->api->getRedirectLoginHelper();
        try {
            $accessToken = $helper->getAccessToken();
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            throw new Hybrid_Exception('Facebook Graph returned an error: ' . $e->getMessage());
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            throw new Hybrid_Exception('Facebook SDK returned an error: ' . $e->getMessage());
        }

        if (!isset($accessToken)) {
            if ($helper->getError()) {
                throw new Hybrid_Exception(sprintf("Could not authorize user, reason: %s (%d)", $helper->getErrorDescription(), $helper->getErrorCode()));
            } else {
                throw new Hybrid_Exception("Could not authorize user. Bad request");
            }
        }

        try {
            // Validate token
            $oAuth2Client = $this->api->getOAuth2Client();
            $tokenMetadata = $oAuth2Client->debugToken($accessToken);
            $tokenMetadata->validateAppId($this->config["keys"]["id"]);
            $tokenMetadata->validateExpiration();

            // Exchanges a short-lived access token for a long-lived one
            if (!$accessToken->isLongLived()) {
                $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
            }
        } catch (FacebookSDKException $e) {
            throw new Hybrid_Exception($e->getMessage(), 0, $e);
        }

        $this->setUserConnected();
        $this->token("access_token", $accessToken->getValue());
    }

    /**
     * {@inheritdoc}
     */
    function logout() {
        parent::logout();
    }

    /**
     * {@inheritdoc}
     */
    function getUserProfile() {
        try {
            $fields = [
                'id',
                'name',
                'first_name',
                'last_name',
                'link',
                'website',
                'gender',
                'locale',
                'about',
                'email',
                'hometown',
                'location',
                'birthday'
            ];
            $response = $this->api->get('/me?fields=' . implode(',', $fields), $this->token('access_token'));
            $data = $response->getDecodedBody();
        } catch (FacebookSDKException $e) {
            throw new Exception("User profile request failed! {$this->providerId} returned an error: {$e->getMessage()}", 6, $e);
        }

        // Store the user profile.
        $this->user->profile->identifier = (array_key_exists('id', $data)) ? $data['id'] : "";
        $this->user->profile->displayName = (array_key_exists('name', $data)) ? $data['name'] : "";
        $this->user->profile->firstName = (array_key_exists('first_name', $data)) ? $data['first_name'] : "";
        $this->user->profile->lastName = (array_key_exists('last_name', $data)) ? $data['last_name'] : "";
        $this->user->profile->photoURL = !empty($this->user->profile->identifier) ? "https://graph.facebook.com/" . $this->user->profile->identifier . "/picture?width=150&height=150" : '';
        $this->user->profile->profileURL = (array_key_exists('link', $data)) ? $data['link'] : "";
        $this->user->profile->webSiteURL = (array_key_exists('website', $data)) ? $data['website'] : "";
        $this->user->profile->gender = (array_key_exists('gender', $data)) ? $data['gender'] : "";
        $this->user->profile->language = (array_key_exists('locale', $data)) ? $data['locale'] : "";
        $this->user->profile->description = (array_key_exists('about', $data)) ? $data['about'] : "";
        $this->user->profile->email = (array_key_exists('email', $data)) ? $data['email'] : "";
        $this->user->profile->emailVerified = (array_key_exists('email', $data)) ? $data['email'] : "";
        $this->user->profile->region = (array_key_exists("location", $data) && array_key_exists("name", $data['location'])) ? $data['location']["name"] : "";

        if (!empty($this->user->profile->region)) {
            $regionArr = explode(',', $this->user->profile->region);
            if (count($regionArr) > 1) {
                $this->user->profile->city = trim($regionArr[0]);
                $this->user->profile->country = trim($regionArr[1]);
            }
        }

        if (array_key_exists('birthday', $data)) {
            $birtydayPieces = explode('/', $data['birthday']);

            if (count($birtydayPieces) == 1) {
                $this->user->profile->birthYear = (int)$birtydayPieces[0];
            } elseif (count($birtydayPieces) == 2) {
                $this->user->profile->birthMonth = (int)$birtydayPieces[0];
                $this->user->profile->birthDay = (int)$birtydayPieces[1];
            } elseif (count($birtydayPieces) == 3) {
                $this->user->profile->birthMonth = (int)$birtydayPieces[0];
                $this->user->profile->birthDay = (int)$birtydayPieces[1];
                $this->user->profile->birthYear = (int)$birtydayPieces[2];
            }
        }

        return $this->user->profile;
    }

    /**
     * Since the Graph API 2.0, the /friends endpoint only returns friend that also use your Facebook app.
     * {@inheritdoc}
     */
    function getUserContacts() {
        $apiCall = '?fields=link,name';
        $returnedContacts = [];
        $pagedList = true;

        while ($pagedList) {
            try {
                $response = $this->api->get('/me/friends' . $apiCall, $this->token('access_token'));
                $response = $response->getDecodedBody();
            } catch (FacebookSDKException $e) {
                throw new Hybrid_Exception("User contacts request failed! {$this->providerId} returned an error {$e->getMessage()}", 0, $e);
            }

            // Prepare the next call if paging links have been returned
            if (array_key_exists('paging', $response) && array_key_exists('next', $response['paging'])) {
                $pagedList = true;
                $next_page = explode('friends', $response['paging']['next']);
                $apiCall = $next_page[1];
            } else {
                $pagedList = false;
            }

            // Add the new page contacts
            $returnedContacts = array_merge($returnedContacts, $response['data']);
        }

        $contacts = [];

        foreach ($returnedContacts as $item) {

            $uc = new Hybrid_User_Contact();
            $uc->identifier = (array_key_exists("id", $item)) ? $item["id"] : "";
            $uc->displayName = (array_key_exists("name", $item)) ? $item["name"] : "";
            $uc->profileURL = (array_key_exists("link", $item)) ? $item["link"] : "https://www.facebook.com/profile.php?id=" . $uc->identifier;
            $uc->photoURL = "https://graph.facebook.com/" . $uc->identifier . "/picture?width=150&height=150";

            $contacts[] = $uc;
        }

        return $contacts;
    }

    /**
     * Load the user latest activity, needs 'read_stream' permission
     *
     * @param string $stream Which activity to fetch:
     *      - timeline : all the stream
     *      - me       : the user activity only
     * {@inheritdoc}
     */
    function getUserActivity($stream = 'timeline') {
        try {
            if ($stream == "me") {
                $response = $this->api->get('/me/feed', $this->token('access_token'));
            } else {
                $response = $this->api->get('/me/home', $this->token('access_token'));
            }
        } catch (FacebookSDKException $e) {
            throw new Hybrid_Exception("User activity stream request failed! {$this->providerId} returned an error: {$e->getMessage()}", 0, $e);
        }

        if (!$response || !count($response['data'])) {
            return [];
        }

        $activities = [];

        foreach ($response['data'] as $item) {

            $ua = new Hybrid_User_Activity();

            $ua->id = (array_key_exists("id", $item)) ? $item["id"] : "";
            $ua->date = (array_key_exists("created_time", $item)) ? strtotime($item["created_time"]) : "";

            if ($item["type"] == "video") {
                $ua->text = (array_key_exists("link", $item)) ? $item["link"] : "";
            }

            if ($item["type"] == "link") {
                $ua->text = (array_key_exists("link", $item)) ? $item["link"] : "";
            }

            if (empty($ua->text) && isset($item["story"])) {
                $ua->text = (array_key_exists("link", $item)) ? $item["link"] : "";
            }

            if (empty($ua->text) && isset($item["message"])) {
                $ua->text = (array_key_exists("message", $item)) ? $item["message"] : "";
            }

            if (!empty($ua->text)) {
                $ua->user->identifier = (array_key_exists("id", $item["from"])) ? $item["from"]["id"] : "";
                $ua->user->displayName = (array_key_exists("name", $item["from"])) ? $item["from"]["name"] : "";
                $ua->user->profileURL = "https://www.facebook.com/profile.php?id=" . $ua->user->identifier;
                $ua->user->photoURL = "https://graph.facebook.com/" . $ua->user->identifier . "/picture?type=square";

                $activities[] = $ua;
            }
        }

        return $activities;
    }

}
