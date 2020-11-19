<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Adapter\AtomInterface;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\RuntimeException;
use Hybridauth\Exception\NotImplementedException;
use Hybridauth\Data;
use Hybridauth\User;
use Hybridauth\Atom\Atom;
use Hybridauth\Atom\Enclosure;
use Hybridauth\Atom\Category;
use Hybridauth\Atom\Author;
use Hybridauth\Atom\AtomFeedBuilder;
use Hybridauth\Atom\AtomHelper;
use Hybridauth\Atom\Filter;

/**
 * YouTube OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback'   => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'       => [ 'id' => '', 'secret' => '' ],
 *       'scope'      => 'https://www.googleapis.com/auth/userinfo.profile
 *           https://www.googleapis.com/auth/youtube.force-ssl',
 *       'channel_id' => '',
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\YouTube( $config );
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   }
 *   catch( Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 */
class YouTube extends OAuth2 implements AtomInterface
{
    /**
     * {@inheritdoc}
     */
    // phpcs:ignore
    protected $scope = 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/youtube.force-ssl';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://www.googleapis.com/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://accounts.google.com/o/oauth2/auth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://accounts.google.com/o/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.google.com/youtube/v3/docs';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'access_type' => 'offline'
        ];

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret
            ];
        }
    }

    /**
     * {@inheritdoc}
     *
     * See: https://developers.google.com/identity/protocols/OpenIDConnect#obtainuserinfo
     */
    public function getUserProfile()
    {
        $userProfile = new User\Profile();

        $response = $this->apiRequest('oauth2/v3/userinfo');

        $data = new Data\Collection($response);

        if (!$data->exists('sub')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile->photoURL    = $data->get('picture');
        if ($this->config->get('photo_size')) {
            $userProfile->photoURL .= '?sz=' . $this->config->get('photo_size');
        }

        $userProfile->language    = $data->get('locale');

        // We'll actually use channel data for certain things...

        $channel = $this->getChannel();

        $snippet = $channel->snippet;

        $userProfile->photoURL = $this->getBestThumbnail($snippet);
        $userProfile->identifier  = $channel->id;
        $userProfile->displayName = $snippet->title;
        if (empty($snippet->customUrl)) {
            $userProfile->profileURL  = 'https://www.youtube.com/channel/' . $channel->id;
        } else {
            $userProfile->profileURL  = $snippet->customUrl;
        }

        return $userProfile;
    }

    /**
     * Get the best thumbnail for a channel.
     *
     * @return ?string
     */
    protected function getBestThumbnail($snippet)
    {
        $photoURL = null;

        $thumbnails = [];
        foreach ($snippet->thumbnails as $key => $thumbnail) {
            if (isset($thumbnail->width)) {
                $width = $thumbnail->width;
            } else {
                switch ($key) {
                    case 'medium':
                        $width = 240;
                        break;
                    case 'high':
                        $width = 800;
                        break;
                    default:
                        $width = 88;
                        break;
                }
            }
            $thumbnails[$thumbnail->url] = $width;
        }

        if ($this->config->get('photo_size')) {
            foreach ($thumbnails as $url => $width) {
                if (($width == $this->config->get('photo_size'))) {
                    $photoURL = $url;
                }
            }
        }
        if ($photoURL === null) {
            $maxWidth = null;
            foreach ($thumbnails as $url => $width) {
                if (($maxWidth === null) || ($width > $maxWidth)) {
                    $maxWidth = $width;
                    $photoURL = $url;
                }
            }
        }

        return $photoURL;
    }

    /**
     * Get the user's channel object.
     *
     * @return object
     * @throws RuntimeException
     */
    protected function getChannel()
    {
        $channelId = $this->config->get('channel_id') ?: null;

        $params = [
            'part' => 'id,snippet,contentDetails',
            'mine' => 'true',
            'maxResults' => '30',
        ];
        $response = $this->apiRequest('youtube/v3/channels', 'GET', $params);

        $data = new Data\Collection($response);

        foreach ($data->get('items') as $channel) {
            if (($channelId === null) || ($data->get('id') == $channelId)) {
                return $channel;
            }
        }

        if ($channelId === null) {
            throw new RuntimeException('Could not find a channel');
        } else {
            throw new RuntimeException('Could not find specified channel, ' . $channelId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $contacts = [];

        $apiUrl = 'youtube/v3/subscriptions';
        $nextPageToken = [];

        do {
            $params = [
                'mine' => 'true',
                'maxResults' => 50,
                'part' => 'snippet',
            ];
            if ($nextPageToken !== null) {
                $params['pageToken'] = $nextPageToken;
            }
            $response = $this->apiRequest($apiUrl, 'GET', $params);

            $data = new Data\Collection($response);

            if (!$data->exists('items')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }

            foreach ($data->get('items') as $item) {
                $contacts[] = $this->fetchUserContact($item);
            }

            if ($data->exists('nextPageToken')) {
                $nextPageToken = $data->get('nextPageToken');
            } else {
                $nextPageToken = null;
            }
        } while ($nextPageToken !== null);

        return $contacts;
    }

    /**
     * Parse the user contact.
     *
     * @param object $item
     *
     * @return \Hybridauth\User\Contact
     */
    protected function fetchUserContact($item)
    {
        $userContact = new User\Contact();

        $snippet = $item->snippet;

        $userContact->identifier = $snippet->channelId;
        $userContact->displayName = $snippet->title;

        $userContact->profileURL = 'https://www.youtube.com/channel/' . $snippet->channelId;

        $userContact->photoURL = $this->getBestThumbnail($snippet);

        return $userContact;
    }

    /**
     * {@inheritdoc}
     */
    public function buildAtomFeed($filter = null, $trulyValid = false)
    {
        $userProfile = $this->getUserProfile();
        list($atoms) = $this->getAtoms($filter);

        $utility = new AtomFeedBuilder();
        $title = 'YouTube feed of ' . $userProfile->displayName;
        $feedId = 'urn:hybridauth:youtube:' . $userProfile->identifier . ':' . md5(serialize(func_get_args()));
        $urnStub = 'urn:hybridauth:youtube:';
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

        $etf = $filter->enclosureTypeFilter;
        if (($etf !== null) && (($etf & Enclosure::ENCLOSURE_VIDEO) != 0)) {
            return $atoms;
        }

        if ($filter->categoryFilter !== null) {
            throw new NotImplementedException('Category filtering is not implementable efficiently');
        }

        $userProfile = $this->getUserProfile();

        $channel = $this->getChannel();

        $playlistId = $channel->contentDetails->relatedPlaylists->uploads;

        $nextPageToken = null;
        do {
            $url = 'youtube/v3/playlistItems';
            $params = [
                'part' => 'id,snippet,status',
                'maxResults' => min(50, $filter->limit - count($atoms)),
                'playlistId' => $playlistId,
            ];
            if ($nextPageToken !== null) {
                $params['pageToken'] = $nextPageToken;
            }
            $videosResponse = $this->apiRequest($url, 'GET', $params);
            $videosData = new Data\Collection($videosResponse);
            if (!$videosData->exists('items')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }

            foreach ($videosData->get('items') as $remoteVideo) {
                $hasResults = true;

                if ((!$filter->includePrivate) && ($remoteVideo->status->privacyStatus != 'public')) {
                    continue;
                }

                $detectedVideo = $this->parseYouTubeVideo($remoteVideo, $userProfile);
                if ($detectedVideo !== null) {
                    $atoms[] = $detectedVideo;

                    if (count($atoms) == $filter->limit) {
                        break 2;
                    }
                }
            }

            if (empty($videosData->get('nextPageToken'))) {
                $nextPageToken = null;
            } else {
                $nextPageToken = $videosData->get('nextPageToken');
            }
        } while ($nextPageToken !== null);

        return [$atoms, $hasResults];
    }

    /**
     * {@inheritdoc}
     */
    public function getAtomFull($identifier)
    {
        $userProfile = $this->getUserProfile();

        $url = 'youtube/v3/videos';
        $params = [
            'part' => 'id,snippet,status',
            'id' => $identifier,
        ];
        $videosResponse = $this->apiRequest($url, 'GET', $params);
        $videosData = new Data\Collection($videosResponse);
        if (!$videosData->exists('items')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        if (empty($videosData->get('items'))) {
            throw new UnexpectedApiResponseException('Could not find the video.');
        }

        return $this->parseYouTubeVideo($videosData->get('items')[0], $userProfile);
    }

    /**
     * Convert a YouTube video into an atom.
     *
     * @param object $remoteVideo Data from video
     * @param User\Profile $userProfile User profile of channel owner
     *
     * @return Atom
     * @throws \Exception
     */
    protected function parseYouTubeVideo($remoteVideo, $userProfile)
    {
        $atom = new Atom();

        $snippet = $remoteVideo->snippet;

        // Find highest resolution thumbnail
        $bestWidth = null;
        $thumbUrl = null;
        foreach ($snippet->thumbnails as $thumbnail) {
            if (($bestWidth === null) || ($thumbnail->width > $bestWidth)) {
                $thumbUrl = $thumbnail->url;
                $bestWidth = $thumbnail->width;
            }
        }

        if (isset($snippet->resourceId->videoId)) {
            $atom->identifier = $snippet->resourceId->videoId;
            $atom->isIncomplete = true; // As comes from playlist
        } else {
            $atom->identifier = $remoteVideo->id;
            $atom->isIncomplete = false;

            $categories = $this->getCategories();
            $categoryId = isset($snippet->categoryId) ? $snippet->categoryId : null;
            $atom->categories = [$categories[$categoryId]];

            $atom->hashTags = $snippet->tags;
        }
        $atom->published = new \DateTime($snippet->publishedAt);
        $atom->title = $snippet->title;
        $atom->url = 'https://www.youtube.com/watch?v=' . $atom->identifier;

        $text = AtomHelper::plainTextToHtml($snippet->description);
        list($text, $repped) = AtomHelper::processCodes($text, null, null, true);
        $atom->content = $text;

        $atom->enclosures = [];
        $enclosure = new Enclosure();
        $enclosure->type = Enclosure::ENCLOSURE_VIDEO;
        $enclosure->url = $atom->url;
        $enclosure->thumbnailUrl = $thumbUrl;
        $atom->enclosures[] = $enclosure;

        $author = new Author();
        $author->identifier = $userProfile->identifier;
        $author->displayName = $userProfile->displayName;
        $author->profileURL = $userProfile->profileURL;
        $author->photoURL = $userProfile->photoURL;
        $atom->author = $author;

        return $atom;
    }

    /**
     * {@inheritdoc}
     */
    public function getAtomFullFromURL($url)
    {
        $matches = [];
        if (preg_match('#^https?://youtu\.be/([\w\-]+)#', $url, $matches) != 0) {
            $identifier = $matches[1];
            return $this->getAtomFull($identifier);
        }
        if (preg_match('#^https?://www\.youtube\.com/watch\?v=([\w\-]+)#', $url, $matches) != 0) {
            $identifier = $matches[1];
            return $this->getAtomFull($identifier);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOEmbedFromURL($url, $params = [])
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function saveAtom($atom, &$messages = [])
    {
        $title = $atom->title;
        AtomHelper::limitLengthTo($title, 70);

        if (($atom->content !== null) && (($atom->summary === null) || (AtomHelper::mbStrlen($atom->content) < 5000))) {
            $description = AtomHelper::htmlToPlainText($atom->content);
        } elseif ($atom->summary !== null) {
            $description = AtomHelper::htmlToPlainText($atom->summary);
        }
        AtomHelper::limitLengthTo($description, 5000);
        if (!empty($atom->url)) {
            AtomHelper::appendIfWithinLimit($description, "\n\n" . $atom->url, 5000);
        }

        if (!empty($atom->categories)) {
            $categoryId = $atom->categories[0]->identifier;
        } else {
            $allCategories = $this->getCategories();
            $keys = array_keys($allCategories);
            $categoryId = $keys[0];
        }

        $request = [
            'snippet' => [
                'title' => $title,
                'description' => $description,
                'tags' => $atom->hashTags,
                'categoryId' => $categoryId,
            ],
        ];

        if ($atom->identifier === null) {
            // Add
            $videoResponse = null;
            $videoId = null;
            foreach ($atom->enclosures as $enclosure) {
                if (in_array($enclosure->type, [Enclosure::ENCLOSURE_VIDEO, Enclosure::ENCLOSURE_AUDIO])) {
                    $videoResponse = $this->uploadVideo($request, $enclosure);
                    if ($videoResponse !== null) {
                        $videoId = $videoResponse->id;
                        try {
                            $thumbnailUploadResponse = $this->uploadThumbnail($videoId, $enclosure);
                        } catch (\Exception $e) {
                            // We can safely ignore exceptions here as they do not matter
                            //  (YouTube will create its own thumbnail)

                            $messages[] = $e->getMessage();
                        }
                    }
                    break;
                }
            }
            if ($videoResponse === null) {
                throw new InvalidArgumentException('No video enclosure, so cannot upload to YouTube');
            }

            return $videoId;
        } else {
            // Edit
            //  Note that this doesn't replace the actual video data, YouTube does not allow that
            //  We could do a delete and re-add, but the ID would change, and atom API disallows this
            $request['id'] = $atom->identifier;
            $url = 'youtube/v3/videos?id=' . urlencode($atom->identifier) . '&part=snippet';
            $headers = [
                'Content-Type' => 'application/json',
            ];
            $this->apiRequest($url, 'PUT', $request, $headers);

            $messages[] = 'Video data not replaced, unsupported by YouTube';

            return $atom->identifier;
        }
    }

    /**
     * Upload actual YouTube video.
     *
     * @param array $request Request structure
     * @param Enclosure $enclosure Video enclosure
     *
     * @return object The metadata response object
     * @throws InvalidArgumentException
     * @throws UnexpectedApiResponseException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    protected function uploadVideo($request, $enclosure)
    {
        $videoResponse = null;

        // Get source file, copying it into a temporary file...

        $mediaType = $enclosure->mimeType;
        $totalBytes = $enclosure->contentLength;

        $allow_url_fopen = @ini_get('allow_url_fopen');
        @ini_set('allow_url_fopen', 'On');

        $myfile = @fopen($enclosure->url, 'rb');
        if ($myfile === false) {
            @ini_set('allow_url_fopen', $allow_url_fopen);

            throw new InvalidArgumentException('Video file not found');
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
        if ($mediaType === null) {
            $mediaType = 'application/octet-stream';
        } else {
            if (strpos($mediaType, 'video/') === false) {
                fclose($myfile);

                throw new InvalidArgumentException('Not a raw web-safe video file');
            }
        }

        if ($totalBytes === null) {
            @ini_set('allow_url_fopen', $allow_url_fopen);

            throw new InvalidArgumentException('Video file size not found');

            fclose($myfile);
        }

        try {
            // Upload metadata...

            stream_set_blocking($myfile, true);

            $chunkSize = 500000;

            $url = 'upload/youtube/v3/videos?uploadType=resumable&part=snippet,status,contentDetails';
            $headers = [
                'X-Upload-Content-Length' => min($chunkSize, $totalBytes),
                'X-Upload-Content-Type' => $mediaType,
                'Content-Type' => 'application/json',
            ];
            $videoResponse = $this->apiRequest($url, 'POST', $request, $headers);

            $responseHeaders = $this->httpClient->getResponseHeader();

            $urlTo = null;
            foreach ($responseHeaders as $header => $value) {
                $matches = [];
                if (strtolower($header) == 'location') {
                    $urlTo = $value;
                }
            }

            if ($urlTo === null) {
                @ini_set('allow_url_fopen', $allow_url_fopen);

                throw new UnexpectedApiResponseException('Upload initialization failed');
            }

            // Upload video data in chunks...

            $i = 0;
            do {
                // Complex code because of HTTP chunked encoding causing fread to finish early
                $data = '';
                do {
                    $data .= fread($myfile, $chunkSize);
                } while ((strlen($data) < $chunkSize) && (!feof($myfile)));

                $header = 'Content-Type: ' . $mediaType;
                $header .= 'Content-Length: ' . strlen($data);
                foreach ($this->apiRequestHeaders as $key => $val) {
                    $header .= "\r\n" . $key . ': ' . $val;
                }

                $opts = ['http' =>
                    [
                        'method'  => 'PUT',
                        'header'  => $header,
                        'content' => $data,
                        'ignore_errors' => true,
                    ]
                ];
                $context  = stream_context_create($opts);

                $uploadResult = file_get_contents($urlTo, false, $context);
                //@var_dump($uploadResult);exit(); //Useful for debugging

                $matches = [];
                $status_line = $http_response_header[0];
                preg_match('#^HTTP\/\S*\s(\d{3})#', $status_line, $matches);
                $status = $matches[1];

                if (substr($status, 0, 1) != '2') {
                    $err = 'Uploading failed on part #' . ($i + 1) . ' [status=' . $status . ']';
                    throw new UnexpectedApiResponseException($err);
                }

                $i++;
            } while (!feof($myfile));
        } catch (\Exception $e) {
            throw $e;
        } finally {
            @ini_set('allow_url_fopen', $allow_url_fopen);

            @fclose($myfile);
        }

        return $videoResponse;
    }

    /**
     * Upload actual YouTube thumbnail.
     *
     * @param string $id Video ID
     * @param Enclosure $enclosure Video enclosure
     *
     * @return ?object Thumbnail upload response object (null: no thumbnail uploaded)
     * @throws InvalidArgumentException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     */
    protected function uploadThumbnail($id, $enclosure)
    {
        if ($enclosure->thumbnailUrl === null) {
            return null;
        }

        // Get source file, copying it into a temporary file...

        $mediaType = $enclosure->mimeType;

        $allow_url_fopen = @ini_get('allow_url_fopen');
        @ini_set('allow_url_fopen', 'On');

        $myfile = @fopen($enclosure->thumbnailUrl, 'rb');
        if ($myfile === false) {
            @ini_set('allow_url_fopen', $allow_url_fopen);

            throw new InvalidArgumentException('Video file not found');
        }
        if (isset($http_response_header)) {
            $matches = [];
            foreach ($http_response_header as $header) {
                if (preg_match('#^Content-Type: ([^/\s]*/[^/\s]*)(\s|;|$)#i', $header, $matches) != 0) {
                    if ($mediaType === null) {
                        $mediaType = $matches[1];
                    }
                }
            }
        }
        if ($mediaType !== null) {
            if (!in_array($mediaType, ['image/png', 'image/jpeg'])) {
                fclose($myfile);

                $err = 'Not an acceptable image file type for the thumbnail (' . $mediaType . ')';
                throw new InvalidArgumentException($err);
            }
        }

        $tempnam = tempnam(sys_get_temp_dir(), 'youtube_thumbnail');
        $tmpfile = fopen($tempnam, 'wb');
        stream_copy_to_stream($myfile, $tmpfile);
        fclose($myfile);

        @ini_set('allow_url_fopen', $allow_url_fopen);

        try {
            $url = 'upload/youtube/v3/thumbnails/set?videoId=' . $id;

            $file = new \CURLFile($tempnam, 'image/png', 'file');

            $thumbnailUploadResponse = $this->apiRequest($url, 'POST', ['file' => $file], [], true);
        } catch (\Exception $e) {
            throw $e;
        } finally {
            @fclose($tmpfile);
            @unlink($tempnam);
        }

        return $thumbnailUploadResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAtom($identifier)
    {
        $this->apiRequest('youtube/v3/videos', 'DELETE', ['id' => $identifier]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategories()
    {
        $country = $this->config->get('primary_country') ?: 'US';

        $params = [
            'part' => 'snippet',
            'regionCode' => $country,
        ];
        $response = $this->apiRequest('youtube/v3/videoCategories', 'GET', $params);

        $data = new Data\Collection($response);

        if (!$data->exists('items')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $categories = [];

        foreach ($data->get('items') as $youTubeCategory) {
            if ($youTubeCategory->snippet->assignable) {
                $category = new Category();
                $category->identifier = $youTubeCategory->id;
                $category->label = $youTubeCategory->snippet->title;
                $categories[$youTubeCategory->id] = $category;
            }
        }

        return $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function saveCategory($category)
    {
        throw new NotImplementedException('Provider does not support this feature.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCategory($identifier)
    {
        throw new NotImplementedException('Provider does not support this feature.');
    }
}
