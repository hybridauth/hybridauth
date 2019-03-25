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
    public $scope = array('email', 'public_profile');

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

        // Include 3rd-party SDK.
        $this->autoLoaderInit();

        $this->api = new FacebookSDK([
            'app_id' => $this->config["keys"]["id"],
            'app_secret' => $this->config["keys"]["secret"],
            'default_graph_version' => !empty($this->config['default_graph_version']) ? $this->config['default_graph_version'] : 'v2.12',
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
        if (isset($_GET['state'])) {
          $helper->getPersistentDataHandler()->set('state', $_GET['state']);
        }
        try {
            $accessToken = $helper->getAccessToken($this->params['login_done']);
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
    * Update user status
    *
    * @param mixed  $status An array describing the status, or string
    * @param string $pageid (optional) User page id
    * @return array
    * @throw Exception
    */
    function setUserStatus($status, $pageid = null) {

      if (!is_array($status)) {
          $status = array('message' => $status);
      }

      $access_token = null;

      if (is_null($pageid)) {
          $pageid = 'me';
          $access_token = $this->token('access_token');

          // if post on page, get access_token page
      } else {

          foreach ($this->getUserPages(true) as $p) {
              if (isset($p['id']) && intval($p['id']) == intval($pageid)) {
                  $access_token = $p['access_token'];
                  break;
              }
          }

          if (is_null($access_token)) {
              throw new Exception("Update user page failed, page not found or not writable!");
          }
      }

      try {
          $response = $this->api->post('/' . $pageid . '/feed', $status, $access_token);
      } catch (FacebookSDKException $e) {
          throw new Exception("Update user status failed! {$this->providerId} returned an error {$e->getMessage()}", 0, $e);
      }

      return $response;
    }

    /**
    * {@inheridoc}
    */
   function getUserPages($writableonly = false) {
       if (!in_array('manage_pages', $this->scope)) {
           throw new Exception("Get user pages requires manage_page permission!");
       }

       try {
           $pages = $this->api->get("/me/accounts", $this->token('access_token'));
           $pages = $pages->getDecodedBody();
       } catch (FacebookApiException $e) {
           throw new Exception("Cannot retrieve user pages! {$this->providerId} returned an error: {$e->getMessage()}", 0, $e);
       }

       if (!isset($pages['data'])) {
           return array();
       }

       if (!$writableonly) {
           return $pages['data'];
       }

       $wrpages = array();
       foreach ($pages['data'] as $p) {
           if (isset($p['perms']) && in_array('CREATE_CONTENT', $p['perms'])) {
               $wrpages[] = $p;
           }
       }

       return $wrpages;
    }

    /**
     * {@inheritdoc}
     */
    function getUserProfile() {
        try {
            $fields = array(
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
            );
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
        $this->user->profile->photoURL = $this->getUserPhoto($this->user->profile->identifier);
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
                $this->user->profile->country = trim(end($regionArr));
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
        if (!in_array('user_friends', $this->scope)) {
           throw new Exception("Get user contacts requires user_friends permission!");
        }

        $apiCall = '?fields=link,name';
        $returnedContacts = array();
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

        $contacts = array();
        foreach ($returnedContacts as $item) {

            $uc = new Hybrid_User_Contact();
            $uc->identifier = (array_key_exists("id", $item)) ? $item["id"] : "";
            $uc->displayName = (array_key_exists("name", $item)) ? $item["name"] : "";
            $uc->profileURL = (array_key_exists("link", $item)) ? $item["link"] : "https://www.facebook.com/profile.php?id=" . $uc->identifier;
            $uc->photoURL = $this->getUserPhoto($uc->identifier);

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
            $response = $response->getDecodedBody();
        } catch (FacebookSDKException $e) {
            throw new Hybrid_Exception("User activity stream request failed! {$this->providerId} returned an error: {$e->getMessage()}", 0, $e);
        }

        if (!$response || !count($response['data'])) {
            return array();
        }

        $activities = array();
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
                $ua->user->photoURL = $this->getUserPhoto($ua->user->identifier);

                $activities[] = $ua;
            }
        }

        return $activities;
    }

    /**
     * Returns a photo URL for give user.
     *
     * @param string $id
     *   The User ID.
     *
     * @return string
     *   A photo URL.
     */
    function getUserPhoto($id) {
        $photo_size = isset($this->config['photo_size']) ? $this->config['photo_size'] : 150;

        return "https://graph.facebook.com/{$id}/picture?width={$photo_size}&height={$photo_size}";
    }

}
