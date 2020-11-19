<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Exception\HttpRequestFailedException;
use Hybridauth\Exception\NotImplementedException;
use Hybridauth\Exception\BadMethodCallException;
use Hybridauth\Adapter\OAuth2;
use Hybridauth\Adapter\AtomInterface;
use Hybridauth\Data\Collection;
use Hybridauth\Data\Parser;
use Hybridauth\User;
use Hybridauth\Atom\Atom;
use Hybridauth\Atom\Enclosure;
use Hybridauth\Atom\Category;
use Hybridauth\Atom\Author;
use Hybridauth\Atom\AtomFeedBuilder;
use Hybridauth\Atom\AtomHelper;
use Hybridauth\Atom\Filter;

/**
 * Facebook OAuth2 provider adapter.
 *
 * Facebook doesn't use standard OAuth refresh tokens.
 * Instead it has a "token exchange" system. You exchange the token prior to
 * expiry, to push back expiry. You start with a short-lived token and each
 * exchange gives you a long-lived one (90 days).
 * We control this with the 'exchange_by_expiry_days' option.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => [ 'id' => '', 'secret' => '' ],
 *       'scope => 'email, user_posts, pages_manage_posts, pages_read_engagement,
 *                  pages_show_list, manage_pages, publish_pages, user_videos',
 *       'exchange_by_expiry_days' => 45, // null for no token exchange
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Facebook($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $response = $adapter->setUserStatus("Hybridauth test message..");
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class Facebook extends OAuth2 implements AtomInterface
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.facebook.com/v8.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.facebook.com/dialog/oauth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://graph.facebook.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.facebook.com/docs/facebook-login/overview';

    /**
     * @var string Profile URL template as the fallback when no `link` returned from the API.
     */
    protected $profileUrlTemplate = 'https://www.facebook.com/%s';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        // Require proof on all Facebook api calls
        // https://developers.facebook.com/docs/graph-api/securing-requests#appsecret_proof
        if ($accessToken = $this->getStoredData('access_token')) {
            $this->apiRequestParameters['appsecret_proof'] = hash_hmac('sha256', $accessToken, $this->clientSecret);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function maintainToken()
    {
        if (!$this->isConnected()) {
            return;
        }

        // Handle token exchange prior to the standard handler for an API request
        $exchange_by_expiry_days = $this->config->get('exchange_by_expiry_days') ?: 45;
        if ($exchange_by_expiry_days !== null) {
            $projected_timestamp = time() + 60 * 60 * 24 * $exchange_by_expiry_days;
            if (!$this->hasAccessTokenExpired() && $this->hasAccessTokenExpired($projected_timestamp)) {
                $this->exchangeAccessToken();
            }
        }
    }

    /**
     * Exchange the Access Token with one that expires further in the future.
     *
     * @return string Raw Provider API response
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    public function exchangeAccessToken()
    {
        $exchangeTokenParameters = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'fb_exchange_token' => $this->getStoredData('access_token'),
        ];

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            'GET',
            $exchangeTokenParameters
        );

        $this->validateApiResponse('Unable to exchange the access token');

        $this->validateAccessTokenExchange($response);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $fields = [
            'id',
            'name',
            'first_name',
            'last_name',
            'website',
            'locale',
            'about',
            'email',
            'hometown',
            'birthday',
        ];

        if (strpos($this->scope, 'user_link') !== false) {
            $fields[] = 'link';
        }

        if (strpos($this->scope, 'user_gender') !== false) {
            $fields[] = 'gender';
        }

        // Note that en_US is needed for gender fields to match convention.
        $locale = $this->config->get('locale') ?: 'en_US';
        $response = $this->apiRequest('me', 'GET', [
            'fields' => implode(',', $fields),
            'locale' => $locale,
        ]);

        $data = new Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->profileURL = $data->get('link');
        $userProfile->webSiteURL = $data->get('website');
        $userProfile->gender = $data->get('gender');
        $userProfile->language = $data->get('locale');
        $userProfile->description = $data->get('about');
        $userProfile->email = $data->get('email');

        // Fallback for profile URL in case Facebook does not provide "pretty" link with username (if user set it).
        if (empty($userProfile->profileURL)) {
            $userProfile->profileURL = $this->getProfileUrl($userProfile->identifier);
        }

        $userProfile->region = $data->filter('hometown')->get('name');

        $userProfile->photoURL = $this->generatePhotoURL($userProfile->identifier);

        $userProfile->emailVerified = $userProfile->email;

        $userProfile = $this->fetchUserRegion($userProfile);

        $userProfile = $this->fetchBirthday($userProfile, $data->get('birthday'));

        return $userProfile;
    }

    /**
     * Generate a photo URL for a user.
     *
     * @param string $identifier
     * @param ?int $photoSize
     *
     * @return string
     */
    protected function generatePhotoURL($identifier, $photoSize = null)
    {
        if ($photoSize === null) {
            $photoSize = intval($this->config->get('photo_size')) ?: 150;
        }

        $photoURL = $this->apiBaseUrl . $identifier . '/picture';
        $photoURL .= '?width=' . strval($photoSize) . '&height=' . strval($photoSize);
        return $photoURL;
    }

    /**
     * Retrieve the user region.
     *
     * @param User\Profile $userProfile
     *
     * @return \Hybridauth\User\Profile
     */
    protected function fetchUserRegion(User\Profile $userProfile)
    {
        if (!empty($userProfile->region)) {
            $regionArr = explode(',', $userProfile->region);

            if (count($regionArr) > 1) {
                $userProfile->city = trim($regionArr[0]);
                $userProfile->country = trim($regionArr[1]);
            }
        }

        return $userProfile;
    }

    /**
     * Retrieve the user birthday.
     *
     * @param User\Profile $userProfile
     * @param string $birthday
     *
     * @return \Hybridauth\User\Profile
     */
    protected function fetchBirthday(User\Profile $userProfile, $birthday)
    {
        $result = (new Parser())->parseBirthday($birthday, '/');

        $userProfile->birthYear = (int)$result[0];
        $userProfile->birthMonth = (int)$result[1];
        $userProfile->birthDay = (int)$result[2];

        return $userProfile;
    }

    /**
     * /v2.0/me/friends only returns the user's friends who also use the app.
     * In the cases where you want to let people tag their friends in stories published by your app,
     * you can use the Taggable Friends API.
     *
     * https://developers.facebook.com/docs/apps/faq#unable_full_friend_list
     */
    public function getUserContacts()
    {
        $contacts = [];

        $apiUrl = 'me/friends?fields=link,name';

        do {
            $response = $this->apiRequest($apiUrl);

            $data = new Collection($response);

            if (!$data->exists('data')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }

            if (!$data->filter('data')->isEmpty()) {
                foreach ($data->filter('data')->toArray() as $item) {
                    $contacts[] = $this->fetchUserContact($item);
                }
            }

            if ($data->filter('paging')->exists('next')) {
                $apiUrl = $data->filter('paging')->get('next');

                $pagedList = true;
            } else {
                $pagedList = false;
            }
        } while ($pagedList);

        return $contacts;
    }

    /**
     * Parse the user contact.
     *
     * @param array $item
     *
     * @return \Hybridauth\User\Contact
     */
    protected function fetchUserContact($item)
    {
        $userContact = new User\Contact();

        $item = new Collection($item);

        $userContact->identifier = $item->get('id');
        $userContact->displayName = $item->get('name');

        $userContact->profileURL = $item->exists('link')
            ?: $this->getProfileUrl($userContact->identifier);

        $userContact->photoURL = $this->generatePhotoURL($userContact->identifier, 150);

        return $userContact;
    }

    /**
     * {@inheritdoc}
     */
    public function setPageStatus($status, $pageId)
    {
        $status = is_string($status) ? ['message' => $status] : $status;

        // Post on user wall.
        if ($pageId === 'me') {
            return $this->setUserStatus($status);
        }

        list(, $tokenHeaders, $tokenParameters) = $this->getPageAccessTokenDetails($pageId);

        $parameters = $tokenParameters + $status;

        return $this->apiRequest("{$pageId}/feed", 'POST', $parameters, $tokenHeaders);
    }

    /**
     * Get a page access token.
     *
     * @param string $pageId Page we need to work with
     *
     * @return array A list: Access token, extra headers for auth, extra parameters for auth
     * @throws InvalidArgumentException
     */
    protected function getPageAccessTokenDetails($pageId)
    {
        // Retrieve writable user pages and filter by given one.
        $pages = $this->getUserPages(true);
        $pages = array_filter($pages, function ($page) use ($pageId) {
            return $page->id == $pageId;
        });

        if (!$pages) {
            throw new InvalidArgumentException('Could not find a writable page with given id.');
        }

        $page = reset($pages);
        $pageAccessToken = $page->access_token;

        // Use page access token instead of user access token.
        $tokenHeaders = [
            'Authorization' => 'Bearer ' . $pageAccessToken,
        ];

        // Refresh proof for API call.
        $tokenParameters = [
            'appsecret_proof' => hash_hmac('sha256', $pageAccessToken, $this->clientSecret),
        ];

        return [$pageAccessToken, $tokenHeaders, $tokenParameters];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPages($writable = false)
    {
        static $cache = [];
        if (isset($cache[$writable])) {
            return $cache[$writable];
        }

        $pages = $this->apiRequest('me/accounts');

        if (!$writable) {
            return $pages->data;
        }

        // Filter user pages by CREATE_CONTENT permission.
        $cache[$writable] = array_filter($pages->data, function ($page) {
            return in_array('CREATE_CONTENT', $page->tasks);
        });

        return $cache[$writable];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream = 'me')
    {
        $apiUrl = $stream == 'me' ? 'me/feed' : 'me/home';

        $response = $this->apiRequest($apiUrl);

        $data = new Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $activities = [];

        foreach ($data->filter('data')->toArray() as $item) {
            $activities[] = $this->fetchUserActivity($item);
        }

        return $activities;
    }

    /**
     * @param $item
     *
     * @return User\Activity
     */
    protected function fetchUserActivity($item)
    {
        $userActivity = new User\Activity();

        $item = new Collection($item);

        $userActivity->id = $item->get('id');
        $userActivity->date = $item->get('created_time');

        if ('video' == $item->get('type') || 'link' == $item->get('type')) {
            $userActivity->text = $item->get('link');
        }

        if (empty($userActivity->text) && $item->exists('story')) {
            $userActivity->text = $item->get('link');
        }

        if (empty($userActivity->text) && $item->exists('message')) {
            $userActivity->text = $item->get('message');
        }

        if (!empty($userActivity->text) && $item->exists('from')) {
            $userActivity->user->identifier = $item->filter('from')->get('id');
            $userActivity->user->displayName = $item->filter('from')->get('name');

            $userActivity->user->profileURL = $this->getProfileUrl($userActivity->user->identifier);

            $userActivity->user->photoURL = $this->generatePhotoURL($userActivity->user->identifier, 150);
        }

        return $userActivity;
    }

    /**
     * Get profile URL.
     *
     * @param int $identity User ID.
     *
     * @return string|null NULL when identity is not provided.
     */
    protected function getProfileUrl($identity)
    {
        if (!is_numeric($identity)) {
            return null;
        }

        return sprintf($this->profileUrlTemplate, $identity);
    }

    /**
     * {@inheritdoc}
     */
    public function buildAtomFeed($filter = null, $trulyValid = false)
    {
        if ($filter === null) {
            $filter = new Filter();
        }

        $category = $this->getDefaultCategory($filter);

        $userProfile = $this->getUserProfile();
        list($atoms) = $this->getAtoms($filter);

        $utility = new AtomFeedBuilder();
        if ($category->identifier == '-') {
            $title = 'Facebook feed of ' . $userProfile->displayName;
        } else {
            $title = 'Facebook feed of ' . $category->label;
        }
        $feedId = 'urn:hybridauth:facebook:' . $userProfile->identifier . ':' . md5(serialize(func_get_args()));
        $urnStub = 'urn:hybridauth:facebook:';
        $url = $userProfile->profileURL;
        return $utility->buildAtomFeed($title, $url, $feedId, $urnStub, $atoms, $trulyValid);
    }

    /**
     * Get default category for reading from.
     *
     * @param \Hybridauth\Atom\Filter $filter Filter
     *
     * @return \Hybridauth\Atom\Category
     */
    protected function getDefaultCategory($filter)
    {
        $categories = $this->getCategories();
        if ($filter->categoryFilter !== null) {
            $category = $categories[$filter->categoryFilter];
        } else {
            $pageId = $this->config->get('default_page_id') ?: '-';

            if (isset($categories[$pageId])) {
                $category = $categories[$pageId];
            } else {
                $category = new Category();
                $category->identifier = $pageId;
                $category->label = $pageId; // Don't know the correct one
            }
        }
        return $category;
    }

    /**
     * {@inheritdoc}
     */
    public function getAtoms($filter = null)
    {
        if ($filter === null) {
            $filter = new Filter();
        }

        $fieldsShared = [
            'id',
            'created_time',
            'from',
            'is_hidden',
            'is_published',
            'message',
            'permalink_url',
            'privacy',

            // Edges
            'attachments',
        ];

        $atoms = [];
        $hasResults = false;

        $category = $this->getDefaultCategory($filter);

        $isPersonal = ($category->identifier == '-');

        if ($isPersonal) {
            $path = 'me';
        } else {
            $path = $category->identifier;
        }
        if ($filter->includeContributedContent) {
            $path .= '/feed';
        } else {
            $path .= '/posts';
        }

        $fields = $fieldsShared;
        if ($isPersonal) {
            // Links done like this for personal feed
            $fields[] = 'type';
            $fields[] = 'link';
            $fields[] = 'name';
            $fields[] = 'description';
        }

        $params = [
            'fields' => implode(',', $fields),
            'limit' => min(100, $filter->limit),
        ];

        do {
            $response = $this->apiRequest($path, 'GET', $params);

            $data = new Collection($response);
            if (!$data->exists('data')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }

            $dataArray = $data->get('data');
            foreach ($dataArray as $item) {
                $hasResults = true;

                // Don't show private stuff
                if (!$filter->includePrivate) {
                    if (!in_array($item->privacy->value, ['ALL_FRIENDS', 'EVERYONE'])) {
                        continue;
                    }
                    if ($item->is_hidden) {
                        continue;
                    }
                }
                if (!$item->is_published) {
                    continue;
                }

                $atom = $this->parseFacebookPost($item, $category, $isPersonal);

                if (!$filter->passesEnclosureTest($atom->enclosures)) {
                    continue;
                }

                $atoms[] = $atom;
                if (count($atoms) == $filter->limit) {
                    break 2;
                }
            }

            if (!empty($data->get('paging')->next)) {
                $queryString = parse_url($data->get('paging')->next, PHP_URL_QUERY);
                parse_str($queryString, $params);
            }
        } while (($filter->deepProbe) && (!empty($dataArray)) && (!empty($data->get('paging')->next)));

        return [$atoms, $hasResults];
    }

    /**
     * {@inheritdoc}
     */
    public function getAtomFull($identifier)
    {
        $fieldsShared = [
            'id',
            'created_time',
            'from',
            'is_hidden',
            'is_published',
            'message',
            'permalink_url',
            'privacy',

            // Edges
            'attachments',
        ];

        $categories = $this->getCategories();

        $identifier_parts = explode('_', $identifier);

        $isPersonal = !isset($categories[$identifier_parts[0]]);

        $path = $identifier;

        $fields = $fieldsShared;
        if ($isPersonal) {
            // Links done like this for personal feed
            $fields[] = 'type';
            $fields[] = 'link';
            $fields[] = 'name';
            $fields[] = 'description';
        }
        $params = [
            'fields' => implode(',', $fields),
        ];

        $item = $this->apiRequest($path, 'GET', $params);

        return $this->parseFacebookPost($item, $categories[$isPersonal ? '-' : $identifier_parts[0]], $isPersonal);
    }

    /**
     * Convert a Facebook post into an atom.
     *
     * @param object $item Data from Facebook
     * @param \Hybridauth\Atom\Category $category Category we're currently operating in
     * @param bool $isPersonal Whether it is from a personal feed
     *
     * @return \Hybridauth\Atom\Atom
     * @throws \Exception
     */
    protected function parseFacebookPost($item, $category, $isPersonal)
    {
        $atom = new Atom();

        // Facebook API varies based on whether it is a personal feed or a page
        $linkURL = null;
        $linkText = null;
        $linkDescription = null;
        if ($isPersonal) {
            $isLink = false;
            if ($item->type == 'link') {
                $isLink = true;
            }
            if ((isset($item->attachments->data[0]->type)) && ($item->attachments->data[0]->type == 'share')) {
                $isLink = true;
            }
            if ($isLink) {
                $linkURL = $item->link;
                $linkText = isset($item->name) ? $item->name : $linkURL;
                $linkDescription = isset($item->description) ? $item->description : '';
            }
        } else {
            $isLink = (isset($item->attachments->data[0]->type)) && ($item->attachments->data[0]->type == 'share');
            if ($isLink) {
                $linkURL = $item->attachments->data[0]->target->url;
                $linkText = isset($item->attachments->data[0]->title) ? $item->attachments->data[0]->title : $linkURL;
                if (isset($item->attachments->data[0]->description)) {
                    $linkDescription = $item->attachments->data[0]->description;
                } else {
                    $linkDescription = '';
                }
            }
        }

        $atom->identifier = $item->id;
        $atom->isIncomplete = false;
        $atom->published = new \DateTime($item->created_time);
        $atom->url = $item->permalink_url;

        $urlUsernames = null;
        $urlHashtags = '<a href="https://facebook.com/hashtag/$1">#$1</a>';
        $detectUrls = true;
        if (!empty($item->message)) {
            $text = $item->message;
        } elseif (!empty($item->name)) {
            $text = $item->name;
        } else {
            $text = '';
        }
        $textPlain = $text;
        $text = AtomHelper::plainTextToHtml($text);
        list($text, $repped) = AtomHelper::processCodes($text, $urlUsernames, $urlHashtags, $detectUrls);
        $atom->content = $text;
        if ($isLink) {
            if (($text == '') && (!empty($linkDescription))) {
                $text = AtomHelper::plainTextToHtml($linkDescription);
                // NB: ^ Intentionally not passed through processCodes
            }
            if ($text != '') {
                $text .= '<br />';
            }
            $text .= '<a href="' . htmlentities($linkURL) . '">' . htmlentities($linkText) . '</a>';
            $atom->content = $text;
        }

        $atom->author = new Author();
        $atom->author->identifier = $item->from->id;
        $atom->author->displayName = $item->from->name;
        $atom->author->profileURL = $this->getProfileUrl($item->from->id);
        $atom->author->photoURL = $this->generatePhotoURL($item->from->id, 150);

        $atom->categories = [];
        $atom->categories[] = $category;

        $atom->enclosures = [];
        if (isset($item->attachments)) {
            foreach ($item->attachments->data as $attachment) {
                $enclosure = $this->processEnclosure($item, $attachment);
                if ($enclosure !== null) {
                    $atom->enclosures[] = $enclosure;

                    if (isset($attachment->subattachments)) {
                        foreach ($attachment->subattachments->data as $subattachment) {
                            $subenclosure = $this->processEnclosure($item, $subattachment);
                            if ($subenclosure !== null) {
                                $atom->enclosures[] = $subenclosure;
                            }
                        }
                    }
                }
            }
        }

        return $atom;
    }

    /**
     * Process an enclosure into an atom attachment.
     *
     * @param object $item Main data from Facebook
     * @param object $attachment Attachment data from Facebook
     *
     * @return ?\Hybridauth\Atom\Enclosure
     */
    protected function processEnclosure($item, $attachment)
    {
        if (!isset($attachment->url)) {
            return null;
        }

        $enclosure = new Enclosure();
        $enclosure->url = $attachment->url;
        switch ($attachment->type) {
            case 'photo':
            case 'photo_inline':
                if (!empty($attachment->media->image->src)) {
                    $enclosure->url = $attachment->media->image->src;
                }
                $enclosure->type = Enclosure::ENCLOSURE_IMAGE;
                break;

            case 'video':
            case 'video_inline':
                $enclosure->type = Enclosure::ENCLOSURE_VIDEO;
                if (isset($attachment->media->image->src)) {
                    $enclosure->thumbnailUrl = $attachment->media->image->src;
                }
                break;

            case 'share':
                $enclosure = null; // This is done as a link
                break;

            default:
                $enclosure->type = Enclosure::ENCLOSURE_BINARY; // We don't recognize this
                break;
        }
        return $enclosure;
    }

    /**
     * {@inheritdoc}
     */
    public function getAtomFullFromURL($url)
    {
        // Can't work now, see https://stackoverflow.com/questions/31353591/how-should-we-retrieve-an-individual-post-now-that-post-id-is-deprecated-in-v
        //  Indications suggest FB management doesn't want us pulling out individual posts for some reason
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOEmbedFromURL($url, $params = [])
    {
        // Note that oEmbed must be enabled on the Facebook App you are using for Instagram

        if (preg_match('#^https://www\.facebook\.com/([^/]+/posts/[^/?]+|.*[&?]id=\d+)#', $url) == 0) {
            return null;
        }

        $appId = $this->config->filter('keys')->get('id') ?: null;
        $clientToken = $this->config->filter('keys')->get('client_token') ?: null;
        if (($appId === null) || ($clientToken === null)) {
            return;
        }
        $comboToken = $appId . '|' . $clientToken;
        $endpoint = 'https://graph.facebook.com/oembed_post?url=' . urlencode($url);
        $endpoint .= '&access_token=' . urlencode($comboToken);
        foreach ($params as $key => $val) {
            $endpoint .= '&' . $key . '=' . urlencode($val);
        }

        $allow_url_fopen = @ini_get('allow_url_fopen');
        @ini_set('allow_url_fopen', 'On');
        $oembed = file_get_contents($endpoint);
        @ini_set('allow_url_fopen', $allow_url_fopen);

        return json_decode($oembed);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAtom($atom, &$messages = [])
    {
        $allCategories = $this->getCategories();

        unset($allCategories['-']);
        if (empty($allCategories)) {
            throw new NotImplementedException('No access to any Facebook page to post to.');
        }

        // Find what Facebook page to post to
        $pageId = null;
        foreach ($atom->categories as $categoryId) {
            foreach ($allCategories as $_pageId => $category) {
                if (($_pageId == $categoryId) || ($category->label == $categoryId)) {
                    $pageId = $_pageId;
                    break 2;
                }
            }
        }
        if ($pageId === null) {
            $pageId = $this->config->get('default_page_id') ?: null;
        }
        if ($pageId === null) {
            $category = array_shift($allCategories);
            $pageId = $category->identifier;
        }

        $path = $pageId . '/feed';

        // Work out message...

        if ($atom->url !== null) {
            $precedence = [[$atom->summary, true], [$atom->title, false], [$atom->content, true]];
        } else {
            $precedence = [[$atom->content, true], [$atom->summary, true], [$atom->title, false]];
        }
        $message = '';
        foreach ($precedence as $_field) {
            list($field, $isHtml) = $_field;
            if (!empty($field)) {
                $message = $isHtml ? AtomHelper::htmlToPlainText($field) : $field;
                break;
            }
        }

        $maximumPostLength = 63206;

        AtomHelper::limitLengthTo($message, $maximumPostLength);

        foreach ($atom->hashTags as $hashTag) {
            AtomHelper::appendIfWithinLimit($message, ' #' . $hashTag, $maximumPostLength);
        }

        $params = [];

        $params['message'] = $message;

        // Misc...

        if ($atom->published !== null) {
            $timestamp = $atom->published->getTimestamp();
        } else {
            $timestamp = null;
        }
        if ($timestamp !== null) {
            $params['backdated_time'] = $timestamp;
        }

        if ($atom->url !== null) {
            $params['link'] = $atom->url;
        }

        // Media...

        if (empty($atom->url)) {
            $allow_url_fopen = @ini_get('allow_url_fopen');
            @ini_set('allow_url_fopen', 'On');

            // Start with images, we can have any number
            $mediaIds = [];
            foreach ($atom->enclosures as $enclosure) {
                try {
                    if ($enclosure->type == Enclosure::ENCLOSURE_IMAGE) {
                        $mediaId = $this->uploadPhoto($pageId, $enclosure);
                        if ($mediaId !== null) {
                            $mediaIds[] = $mediaId;
                        }
                    }
                } catch (HttpRequestFailedException $e) {
                    // FB could give all kinds of issues, should not stop our post
                }
            }

            // FB doesn't document this, but we can only post one lone video, unattached
            //  So we do this in a weird way
            if (empty($mediaIds)) {
                foreach ($atom->enclosures as $enclosure) {
                    try {
                        if (in_array($enclosure->type, [Enclosure::ENCLOSURE_VIDEO, Enclosure::ENCLOSURE_AUDIO])) {
                            $mediaId = $this->uploadVideo($pageId, $enclosure, $message, $timestamp);
                            if ($mediaId !== null) {
                                return $mediaId;
                            }
                        }
                    } catch (\Exception $e) {
                        // FB could give all kinds of issues, should not stop our post
                    }
                }
            }

            if (!empty($mediaIds)) {
                $params['attached_media'] = [];
                foreach ($mediaIds as $i => $mediaId) {
                    $params['attached_media'][] = ['media_fbid' => $mediaId];
                }
            }

            @ini_set('allow_url_fopen', $allow_url_fopen);
        }

        // Go ahead...

        list(, $tokenHeaders, $tokenParameters) = $this->getPageAccessTokenDetails($pageId);

        $params += $tokenParameters;

        $headers = ['Content-Type' => 'application/json'];
        $headers += $tokenHeaders;

        $result = $this->apiRequest($path, 'POST', $params, $headers, true);
        return $result->id;
    }

    /**
     * Upload a photo.
     *
     * @param string $pageId Page to post to
     * @param \Hybridauth\Atom\Enclosure $enclosure
     *
     * @return ?string Photo ID
     * @throws HttpRequestFailedException
     * @throws InvalidArgumentException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    protected function uploadPhoto($pageId, $enclosure)
    {
        $mediaType = $enclosure->mimeType;
        $totalBytes = $enclosure->contentLength;

        if (($mediaType === null) || ($totalBytes === null)) {
            // We need to look this up by calling HTTP early
            $myfile = @fopen($enclosure->url, 'rb');
            if ($myfile === false) {
                return null;
            }
            if (isset($http_response_header)) {
                $matches = [];
                foreach ($http_response_header as $header) {
                    if (preg_match('#^Content-Type: ([^/\s]*/[^/\s]*)(\s|;|$)#i', $header, $matches) != 0) {
                        if ($mediaType === null) {
                            $mediaType = $matches[1];
                        }
                    } elseif (preg_match('#^Content-Length: (\d+)*(;|$)#i', $header, $matches) != 0) {
                        $totalBytes = intval($matches[1]);
                    }
                }
            }
            fclose($myfile);
            if (($mediaType === null) || ($totalBytes === null)) {
                return null;
            }
        }

        if (!in_array($mediaType, ['image/gif', 'image/png', 'image/jpeg', 'image/tiff', 'image/bmp'])) {
            return null;
        }

        $maxSize = 4 * 1000 * 1000; // An FB limit

        if ($totalBytes > $maxSize) {
            return null;
        }

        $path = $pageId . '/photos';

        $params = [
            'url' => $enclosure->url,
            'no_story' => true,
            'published' => false,
        ];

        list(, $tokenHeaders, $tokenParameters) = $this->getPageAccessTokenDetails($pageId);

        $params += $tokenParameters;

        $result = $this->apiRequest($path, 'POST', $params, $tokenHeaders);

        return $result->id;
    }

    /**
     * Upload a video.
     *
     * @param string $pageId Page to post to
     * @param \Hybridauth\Atom\Enclosure $enclosure
     * @param string $message
     * @param integer $timestamp
     *
     * @return ?string Video ID
     * @throws HttpRequestFailedException
     * @throws InvalidArgumentException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    protected function uploadVideo($pageId, $enclosure, $message, $timestamp)
    {
        $mediaType = $enclosure->mimeType;
        $totalBytes = $enclosure->contentLength;

        list(, $tokenHeaders, $tokenParameters) = $this->getPageAccessTokenDetails($pageId);

        if (($mediaType === null) || ($totalBytes === null)) {
            // We need to look this up by calling HTTP early
            $myfile = @fopen($enclosure->url, 'rb');
            if ($myfile === false) {
                return null;
            }
            if (isset($http_response_header)) {
                $matches = [];
                foreach ($http_response_header as $header) {
                    if (preg_match('#^Content-Type: ([^/\s]*/[^/\s]*)(\s|;|$)#i', $header, $matches) != 0) {
                        if ($mediaType === null) {
                            $mediaType = $matches[1];
                        }
                    } elseif (preg_match('#^Content-Length: (\d+)*(;|$)#i', $header, $matches) != 0) {
                        $totalBytes = intval($matches[1]);
                    }
                }
            }
            fclose($myfile);
            if (($mediaType === null) || ($totalBytes === null)) {
                return null;
            }
        } else {
            $myfile = null;
        }

        $apiUrl = 'https://graph-video.facebook.com/' . $pageId . '/videos';

        $params = [
            'file_url' => $enclosure->url,
            'description' => $message,
        ];

        if ($timestamp !== null) {
            $params['backdated_post'] = [
                'backdated_time' => $timestamp,
            ];
        }

        if ($enclosure->thumbnailUrl !== null) {
            $thumbData = @file_get_contents($enclosure->thumbnailUrl);
            if (!empty($thumbData)) {
                $params['thumb'] = $thumbData;
            }
        }

        $params += $tokenParameters;

        $headers = ['Content-Type' => 'application/json'];
        $headers += $tokenHeaders;

        $result = $this->apiRequest($apiUrl, 'POST', $params, $headers, true);

        return $result->id;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAtom($identifier)
    {
        if (strpos($identifier, '/') !== false) {
            throw new BadMethodCallException('$identifier cannot include a slash.');
        }

        $this->apiRequest($identifier, 'DELETE');
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories()
    {
        $dataArray = $this->getUserPages(true);

        static $categories = [];
        if (!empty($categories)) {
            return $categories;
        }

        $category = new Category();
        $category->identifier = '-';
        $category->label = 'Personal feed';
        $categories['-'] = $category;

        foreach ($dataArray as $item) {
            $category = new Category();
            $category->identifier = $item->id;
            $category->label = $item->name;
            $categories[$category->identifier] = $category;
        }

        return $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function saveCategory($category)
    {
        // Could in theory implement, but nobody would want their Pages being manipulated by this API
        throw new NotImplementedException('Provider does not support this feature.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCategory($identifier)
    {
        // Could in theory implement, but nobody would want their Pages being manipulated by this API
        throw new NotImplementedException('Provider does not support this feature.');
    }
}
