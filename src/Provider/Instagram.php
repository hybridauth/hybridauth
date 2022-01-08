<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Adapter\AtomInterface;
use Hybridauth\Data\Collection;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Exception\BadMethodCallException;
use Hybridauth\Exception\NotImplementedException;
use Hybridauth\User;
use Hybridauth\Atom\Atom;
use Hybridauth\Atom\Enclosure;
use Hybridauth\Atom\Author;
use Hybridauth\Atom\AtomFeedBuilder;
use Hybridauth\Atom\AtomHelper;
use Hybridauth\Atom\Filter;

/**
 * Instagram OAuth2 provider adapter via Instagram Basic Display API.
 */
class Instagram extends OAuth2 implements AtomInterface
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user_profile,user_media';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.instagram.com';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.instagram.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.instagram.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.facebook.com/docs/instagram-basic-display-api';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        // The Instagram API requires an access_token from authenticated users
        // for each endpoint.
        $accessToken = $this->getStoredData($this->accessTokenName);
        $this->apiRequestParameters[$this->accessTokenName] = $accessToken;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        if (!$collection->exists('expires_in')) {
            // Instagram tokens always expire in an hour, but this is implicit not explicit

            $expires_in = 60 * 60;

            $expires_at = time() + $expires_in;

            $this->storeData('expires_in', $expires_in);
            $this->storeData('expires_at', $expires_at);
        }

        return $collection;
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
        if ($this->getStoredData('expires_in') >= 5000000) {
            /*
            Refresh a long-lived token (needed on Instagram, but not Facebook).
            It's not an oAuth style refresh using a refresh token.
            Actually it's really just another exchange, and invalidates the old token.
            Facebook/Instagram documentation is not very helpful at explaining that!
            */
            $exchangeTokenParameters = [
                'grant_type'        => 'ig_refresh_token',
                'client_secret'     => $this->clientSecret,
                'access_token'      => $this->getStoredData('access_token'),
            ];
            $url = 'https://graph.instagram.com/refresh_access_token';
        } else {
            // Exchange short-lived to long-lived
            $exchangeTokenParameters = [
                'grant_type'        => 'ig_exchange_token',
                'client_secret'     => $this->clientSecret,
                'access_token'      => $this->getStoredData('access_token'),
            ];
            $url = 'https://graph.instagram.com/access_token';
        }

        $response = $this->httpClient->request(
            $url,
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
        $response = $this->apiRequest('me', 'GET', [
            'fields' => 'id,username,account_type,media_count',
        ]);

        $data = new Collection($response);
        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('id');
        $userProfile->displayName = $data->get('username');
        $userProfile->profileURL = "https://instagram.com/{$userProfile->displayName}";
        $userProfile->data = [
            'account_type' => $data->get('account_type'),
            'media_count' => $data->get('media_count'),
        ];

        return $userProfile;
    }

    /**
     * Fetch user medias.
     *
     * @param int $limit Number of elements per page.
     * @param string $pageId Current pager ID.
     * @param array|null $fields Fields to fetch per media.
     *
     * @return \Hybridauth\Data\Collection
     *
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     * @throws \Hybridauth\Exception\UnexpectedApiResponseException
     */
    public function getUserMedia($limit = 12, $pageId = null, array $fields = null)
    {
        if (empty($fields)) {
            $fields = [
                'id',
                'caption',
                'media_type',
                'media_url',
                'thumbnail_url',
                'permalink',
                'timestamp',
                'username',
            ];
        }

        $params = [
            'fields' => implode(',', $fields),
            'limit' => $limit,
        ];
        if ($pageId !== null) {
            $params['after'] = $pageId;
        }

        $response = $this->apiRequest('me/media', 'GET', $params);

        $data = new Collection($response);
        if (!$data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        return $data;
    }

    /**
     * Fetches a single user's media.
     *
     * @param string $mediaId Media ID.
     * @param array|null $fields Fields to fetch per media.
     *
     * @return \Hybridauth\Data\Collection
     *
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     * @throws \Hybridauth\Exception\UnexpectedApiResponseException
     */
    public function getMedia($mediaId, array $fields = null)
    {
        if (empty($fields)) {
            $fields = [
                'id',
                'caption',
                'media_type',
                'media_url',
                'thumbnail_url',
                'permalink',
                'timestamp',
                'username',
            ];
        }

        $response = $this->apiRequest($mediaId, 'GET', [
            'fields' => implode(',', $fields),
        ]);

        $data = new Collection($response);
        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function buildAtomFeed($filter = null, $trulyValid = false)
    {
        $userProfile = $this->getUserProfile();
        list($atoms) = $this->getAtoms($filter);

        $utility = new AtomFeedBuilder();
        $title = 'Instagram feed of ' . $userProfile->displayName;
        $feedId = 'urn:hybridauth:instagram:' . $userProfile->identifier . ':' . md5(serialize(func_get_args()));
        $urnStub = 'urn:hybridauth:instagram:';
        $url = $userProfile->profileURL;
        return $utility->buildAtomFeed($title, $url, $feedId, $urnStub, $atoms, $trulyValid);
    }

    /**
     * {@inheritdoc}
     */
    public function getAtoms($filter = null)
    {
        if ($filter === null) {
            $filter = new Filter();
        }

        $atoms = [];
        $hasResults = false;
        $pagination = null;

        $fields = [
            'caption',
            'id',
            'media_type',
            'media_url',
            'permalink',
            'thumbnail_url',
            'timestamp',
            'username',

            // Edges
            'children',
        ];

        $params = [
            'fields' => implode(',', $fields),
        ];

        do {
            $response = $this->apiRequest('me/media', 'GET', $params);

            $data = new Collection($response);
            if (!$data->exists('data')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }

            $dataArray = $data->get('data');

            foreach ($dataArray as $item) {
                $hasResults = true;

                $atom = $this->parseInstagramMediaItem($item);

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
        $fields = [
            'caption',
            'id',
            'media_type',
            'media_url',
            'permalink',
            'thumbnail_url',
            'timestamp',
            'username',

            // Edges
            'children',
        ];

        $params = [
            'fields' => implode(',', $fields),
        ];

        if (strpos($identifier, '/') !== false) {
            throw new BadMethodCallException('$identifier cannot include a slash.');
        }

        $data = $this->apiRequest($identifier, 'GET', $params);

        return $this->parseInstagramMediaItem($data);
    }

    /**
     * Convert an Instagram media item into an atom.
     *
     * @param object $item
     *
     * @return \Hybridauth\Atom\Atom
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     * @throws \Exception
     */
    protected function parseInstagramMediaItem($item)
    {
        $atom = new Atom();

        $atom->identifier = $item->id;
        $atom->isIncomplete = false;
        $atom->categories = [];
        $atom->published = new \DateTime($item->timestamp);
        $atom->url = $item->permalink;

        if (!empty($item->caption)) {
            $urlUsernames = '<a href="http://instagram.com/$1">@$1</a>';
            $urlHashtags = '<a href="https://instagram.com/explore/tags/$1/">#$1</a>';
            $detectUrls = true;
            $text = $item->caption;
            $text = AtomHelper::plainTextToHtml($text);
            list($text, $repped) = AtomHelper::processCodes($text, $urlUsernames, $urlHashtags, $detectUrls);
            $atom->content = $text;
        }

        $atom->author = new Author();
        $atom->author->identifier = $item->username;
        $atom->author->displayName = $item->username;
        $atom->author->profileURL = "https://instagram.com/{$item->username}";

        $atom->enclosures = $this->parseInstagramMediaItemEnclosure($item);

        return $atom;
    }

    /**
     * Convert Instagram file media to an enclosure(s).
     *
     * @param object $item
     *
     * @return array List of enclosures
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    protected function parseInstagramMediaItemEnclosure($item)
    {
        $enclosures = [];

        switch ($item->media_type) {
            case 'IMAGE':
                $enclosure = new Enclosure();
                $enclosure->url = $item->media_url;
                $enclosure->type = Enclosure::ENCLOSURE_IMAGE;
                $enclosures[] = $enclosure;
                break;

            case 'VIDEO':
                $enclosure = new Enclosure();
                $enclosure->url = $item->media_url;
                $enclosure->type = Enclosure::ENCLOSURE_VIDEO;
                $enclosure->thumbnailUrl = $item->thumbnail_url;
                $enclosures[] = $enclosure;
                break;

            case 'CAROUSEL_ALBUM':
                foreach ($item->children->data as $child) {
                    $fields = [
                        'media_type',
                        'media_url',
                        'thumbnail_url',
                    ];

                    $params = [
                        'fields' => implode(',', $fields),
                    ];


                    $data = $this->apiRequest($child->id, 'GET', $params);
                    $enclosures = array_merge($enclosures, $this->parseInstagramMediaItemEnclosure($data));
                }
                break;

            default:
                $enclosure = new Enclosure();
                $enclosure->url = $item->media_url;
                $enclosure->type = Enclosure::ENCLOSURE_BINARY; // We don't recognize this
                $enclosures[] = $enclosure;
                break;
        }

        return $enclosures;
    }

    /**
     * {@inheritdoc}
     */
    public function getAtomFullFromURL($url)
    {
        // Can't work, the permalink tokens are not media IDs, and even when converted they're old style ones
        //  Indications suggest FB management doesn't want us pulling out individual posts for some reason
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOEmbedFromURL($url, $params = [])
    {
        // Note that oEmbed must be enabled on the Facebook App you are using for Instagram

        if (preg_match('#^https://www\.instagram\.com/p/[^/]+#', $url) == 0) {
            return null;
        }

        $appId = $this->config->filter('keys')->get('id') ?: null;
        $clientToken = $this->config->filter('keys')->get('client_token') ?: null;
        if (($appId === null) || ($clientToken === null)) {
            return;
        }
        $comboToken = $appId . '|' . $clientToken;
        $endpoint = 'https://graph.facebook.com/instagram_oembed?url=' . urlencode($url);
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
        throw new NotImplementedException('There is no write access on the Instagram APIs.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAtom($identifier)
    {
        throw new NotImplementedException('There is no write access on the Instagram APIs.');
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function saveCategory($category)
    {
        throw new NotImplementedException('There are no categories on Instagram.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCategory($identifier)
    {
        throw new NotImplementedException('There are no categories on Instagram.');
    }
}
