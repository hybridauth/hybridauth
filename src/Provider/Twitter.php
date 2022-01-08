<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth1;
use Hybridauth\Adapter\AtomInterface;
use Hybridauth\Exception\NotImplementedException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data\Collection;
use Hybridauth\User;
use Hybridauth\Atom\Atom;
use Hybridauth\Atom\Enclosure;
use Hybridauth\Atom\Author;
use Hybridauth\Atom\AtomFeedBuilder;
use Hybridauth\Atom\AtomHelper;
use Hybridauth\Atom\Filter;

/**
 * Twitter OAuth1 provider adapter.
 * Uses OAuth1 not OAuth2 because many Twitter endpoints are built around OAuth1.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => [ 'key' => '', 'secret' => '' ], // OAuth1 uses 'key' not 'id'
 *       'authorize' => true // Needed to perform actions on behalf of users (see below link)
 *         // https://developer.twitter.com/en/docs/authentication/oauth-1-0a/obtaining-user-access-tokens
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Twitter($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $contacts = $adapter->getUserContacts(['screen_name' =>'andypiper']); // get those of @andypiper
 *       $activity = $adapter->getUserActivity('me');
 *   } catch (\Exception $e) {
 *       echo $e->getMessage() ;
 *   }
 */
class Twitter extends OAuth1 implements AtomInterface
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.twitter.com/1.1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.twitter.com/oauth/authenticate';

    /**
     * {@inheritdoc}
     */
    protected $requestTokenUrl = 'https://api.twitter.com/oauth/request_token';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://dev.twitter.com/web/sign-in/implementing';

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl($parameters = [])
    {
        if ($this->config->get('authorize') === true) {
            $this->authorizeUrl = 'https://api.twitter.com/oauth/authorize';
        }

        return parent::getAuthorizeUrl($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('account/verify_credentials.json', 'GET', [
            'include_email' => $this->config->get('include_email') === false ? 'false' : 'true',
        ]);

        $data = new Collection($response);

        if (!$data->exists('id_str')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('id_str');
        $userProfile->displayName = $data->get('screen_name');
        $userProfile->description = $data->get('description');
        $userProfile->firstName = $data->get('name');
        $userProfile->email = $data->get('email');
        $userProfile->emailVerified = $data->get('email');
        $userProfile->webSiteURL = $data->get('url');
        $userProfile->region = $data->get('location');

        $userProfile->profileURL = $data->exists('screen_name')
            ? ('https://twitter.com/' . $data->get('screen_name'))
            : '';

        $photoSize = $this->config->get('photo_size') ?: 'original';
        $photoSize = $photoSize === 'original' ? '' : "_{$photoSize}";
        $userProfile->photoURL = $data->exists('profile_image_url_https')
            ? str_replace('_normal', $photoSize, $data->get('profile_image_url_https'))
            : '';

        $userProfile->data = [
            'followed_by' => $data->get('followers_count'),
            'follows' => $data->get('friends_count'),
        ];

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts($parameters = [])
    {
        $parameters = ['cursor' => '-1'] + $parameters;

        $response = $this->apiRequest('friends/ids.json', 'GET', $parameters);

        $data = new Collection($response);

        if (!$data->exists('ids')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        if ($data->filter('ids')->isEmpty()) {
            return [];
        }

        $contacts = [];

        // 75 id per time should be okey
        $contactsIds = array_chunk((array)$data->get('ids'), 75);

        foreach ($contactsIds as $chunk) {
            $parameters = ['user_id' => implode(',', $chunk)];

            try {
                $response = $this->apiRequest('users/lookup.json', 'GET', $parameters);

                if ($response && count($response)) {
                    foreach ($response as $item) {
                        $contacts[] = $this->fetchUserContact($item);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $contacts;
    }

    /**
     * @param $item
     *
     * @return User\Contact
     */
    protected function fetchUserContact($item)
    {
        $item = new Collection($item);

        $userContact = new User\Contact();

        $userContact->identifier = $item->get('id_str');
        $userContact->displayName = $item->get('name');
        $userContact->photoURL = $item->get('profile_image_url');
        $userContact->description = $item->get('description');

        $userContact->profileURL = $item->exists('screen_name')
            ? ('https://twitter.com/' . $item->get('screen_name'))
            : '';

        return $userContact;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        if (is_string($status)) {
            $status = ['status' => $status];
        }

        // Prepare request parameters.
        $params = [];
        if (isset($status['status'])) {
            $params['status'] = $status['status'];
        }
        if (isset($status['picture'])) {
            $media = $this->apiRequest('https://upload.twitter.com/1.1/media/upload.json', 'POST', [
                'media' => base64_encode(file_get_contents($status['picture'])),
            ]);
            $params['media_ids'] = $media->media_id;
        }

        return $this->apiRequest('statuses/update.json', 'POST', $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream = 'me')
    {
        $apiUrl = ($stream == 'me')
            ? 'statuses/user_timeline.json'
            : 'statuses/home_timeline.json';

        $response = $this->apiRequest($apiUrl);

        if (!$response) {
            return [];
        }

        $activities = [];

        foreach ($response as $item) {
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
        $item = new Collection($item);

        $userActivity = new User\Activity();

        $userActivity->id = $item->get('id_str');
        $userActivity->date = $item->get('created_at');
        $userActivity->text = $item->get('text');

        $userActivity->user->identifier = $item->filter('user')->get('id_str');
        $userActivity->user->displayName = $item->filter('user')->get('name');
        $userActivity->user->photoURL = $item->filter('user')->get('profile_image_url');

        $userActivity->user->profileURL = $item->filter('user')->get('screen_name')
            ? ('https://twitter.com/' . $item->filter('user')->get('screen_name'))
            : '';

        return $userActivity;
    }

    /**
     * {@inheritdoc}
     */
    public function buildAtomFeed($filter = null, $trulyValid = false)
    {
        $userProfile = $this->getUserProfile();
        list($atoms) = $this->getAtoms($filter);

        $utility = new AtomFeedBuilder();
        $title = 'Twitter feed of ' . $userProfile->displayName;
        $feedId = 'urn:hybridauth:twitter:' . $userProfile->identifier . ':' . md5(serialize(func_get_args()));
        $urnStub = 'urn:hybridauth:twitter:';
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

        $apiUrl = 'statuses/user_timeline.json';

        $atoms = [];
        $hasResults = false;

        $params = [
            'count' => min(200, $filter->limit),
        ];

        do {
            $response = $this->apiRequest($apiUrl, 'GET', $params);

            $data = new Collection($response);
            $dataArray = $data->toArray();

            foreach ($dataArray as $item) {
                $hasResults = true;

                $params['max_id'] = $item->id_str;

                $atom = $this->parseTweet($item);

                if (!$filter->passesEnclosureTest($atom->enclosures)) {
                    continue;
                }

                $atoms[] = $atom;
                if (count($atoms) == $filter->limit) {
                    break 2;
                }
            }
        } while (($filter->deepProbe) && (!empty($dataArray)) && (isset($params['max_id'])));

        return [$atoms, $hasResults];
    }

    /**
     * {@inheritdoc}
     */
    public function getAtomFull($identifier)
    {
        $apiUrl = 'statuses/show/' . $identifier . '.json';

        $item = $this->apiRequest($apiUrl);

        return $this->parseTweet($item);
    }

    /**
     * Convert a Tweet into an atom.
     *
     * @param object $item
     *
     * @return \Hybridauth\Atom\Atom
     * @throws \Exception
     */
    protected function parseTweet($item)
    {
        $atom = new Atom();

        $atom->identifier = $item->id_str;
        $atom->isIncomplete = false;
        $atom->published = new \DateTime($item->created_at);
        $atom->url = "https://twitter.com/{$item->user->screen_name}/status/{$item->id_str}";

        $urlUsernames = '<a href="http://twitter.com/$1">@$1</a>';
        $urlHashtags = '<a href="https://twitter.com/hashtag/$1?src=hash">#$1</a>';
        $detectUrls = true;
        list($text, $repped) = AtomHelper::processCodes($item->text, $urlUsernames, $urlHashtags, $detectUrls);
        if ((!$repped) && (AtomHelper::plainTextToHtml(AtomHelper::htmlToPlainText($text)) == $text)) {
            $atom->title = $text;
        } else {
            $atom->content = $text;
        }

        $atom->author = new Author();
        $atom->author->identifier = strval($item->user->id);
        $atom->author->displayName = $item->user->screen_name;
        $atom->author->profileURL = 'https://twitter.com/' . $item->user->screen_name;
        if (!empty($item->user->profile_image_url_https)) {
            $atom->author->photoURL = str_replace('_normal', '', $item->user->profile_image_url_https);
        }

        $enclosures = [];
        if (isset($item->extended_entities)) {
            foreach ($item->extended_entities->media as $entity) {
                $enclosure = new Enclosure();

                switch ($entity->type) {
                    case 'photo':
                        $enclosure->type = Enclosure::ENCLOSURE_IMAGE;
                        $enclosure->url = $entity->media_url_https;
                        $enclosure->mimeType = Enclosure::guessMimeType($enclosure->url);
                        $enclosure->thumbnailUrl = $entity->media_url_https . '?name=thumb';
                        break;

                    case 'video':
                    case 'animated_gif':
                        $enclosure->type = Enclosure::ENCLOSURE_VIDEO;
                        $enclosure->url = $entity->video_info->variants[0]->url;
                        $enclosure->mimeType = $entity->video_info->variants[0]->content_type;
                        $enclosure->thumbnailUrl = $entity->media_url_https;
                        break;

                    default:
                        $enclosure->type = Enclosure::ENCLOSURE_BINARY;
                        $enclosure->url = $entity->media_url_https;
                        break;
                }

                $enclosures[] = $enclosure;
            }
        }
        $atom->enclosures = $enclosures;

        return $atom;
    }

    /**
     * {@inheritdoc}
     */
    public function getAtomFullFromURL($url)
    {
        $ret = null;

        $matches = [];
        if (preg_match('#^https://twitter\.com/[^/]+/status/(\d+)#', $url, $matches) != 0) {
            $identifier = $matches[1];
            $ret = $this->getAtomFull($identifier);
        }

        return $ret;
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
        if ($atom->identifier !== null) {
            throw new NotImplementedException('Twitter does not allow edits or identifier-specifying.');
        }

        // Work out status...

        // Lots of work to stay within the Twitter limits!

        $maxLength = intval($this->config->get('max_length')) ?: 240;
        $segmentSize = intval($this->config->get('segment_size')) ?: (512 * 1024);

        $ellipsis = hex2bin('E280A6'); // Can be made cleaner in PHP-8

        $parts = []; // Will be maximum of 2
        if (!empty($atom->title)) {
            $parts[] = $atom->title;
            if ((!empty($atom->url)) && (strpos($parts[0], $atom->url) === false)) {
                $parts[] = $atom->url;
            }
        } elseif (!empty($atom->summary)) {
            $parts[] = AtomHelper::htmlToPlainText($atom->summary);
            if ((!empty($atom->url)) && (strpos($parts[0], $atom->url) === false)) {
                $parts[] = $atom->url;
            }
        } elseif (!empty($atom->content)) {
            $parts[] = AtomHelper::htmlToPlainText($atom->content);
            if ((!empty($atom->url)) && (strpos($parts[0], $atom->url) === false)) {
                $parts[] = $atom->url;
            }
        } else {
            $parts[] = '';
        }
        if ((count($parts) == 2) && (AtomHelper::mbStrlen($parts[1]) > $maxLength)) {
            // URL too long to include
            unset($parts[1]);
        }
        if ((count($parts) == 2) && (AtomHelper::mbStrlen($parts[1]) >= $maxLength - 2)) {
            // Only space for URL (considering joining space also and the ellipsis)
            unset($parts[0]);
            $parts = array_values($parts);
        }
        if (count($parts) == 2) {
            if (AtomHelper::mbStrlen($parts[0] . ' ' . $parts[1]) <= $maxLength) {
                $status = $parts[0] . ' ' . $parts[1];
            } else {
                $maxLength -= AtomHelper::mbStrlen($parts[1]);
                $maxLength -= 2; // considering joining space also and the ellipsis
                $status = AtomHelper::mbSubstr($parts[0], 0, $maxLength) . $ellipsis . ' ' . $parts[1];
            }
        } else {
            if (AtomHelper::mbStrlen($parts[0]) <= $maxLength) {
                $status = $parts[0];
            } else {
                $maxLength--; // for the ellipsis
                $status = AtomHelper::mbSubstr($parts[0], 0, $maxLength) . $ellipsis;
            }
        }

        foreach ($atom->hashTags as $hashTag) {
            AtomHelper::appendIfWithinLimit($status, ' #' . $hashTag, $maxLength);
        }

        // Uploading as required...

        $allow_url_fopen = @ini_get('allow_url_fopen');
        @ini_set('allow_url_fopen', 'On');

        $mediaIds = [];
        $numImagesDone = 0;
        $numVideosDone = 0;
        foreach ($atom->enclosures as $enclosure) {
            $mediaType = $enclosure->mimeType;
            $totalBytes = $enclosure->contentLength;

            if (($mediaType === null) || ($totalBytes === null)) {
                // We need to look this up by calling HTTP early
                $myfile = @fopen($enclosure->url, 'rb');
                if ($myfile === false) {
                    continue;
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
                if (($mediaType === null) || ($totalBytes === null)) {
                    fclose($myfile);
                    continue;
                }
            } else {
                $myfile = null;
            }

            switch ($enclosure->type) {
                case Enclosure::ENCLOSURE_IMAGE:
                    $gif = ($mediaType == 'image/gif');

                    if (!$gif) {
                        if (($numImagesDone > 4) || ($numVideosDone > 0)) {
                            continue 2;
                        }

                        $numImagesDone++;

                        if (!in_array($mediaType, ['image/jpeg', 'image/png', 'image/webp'])) {
                            continue 2;
                        }

                        if ($totalBytes > 5000000) {
                            continue 2;
                        }

                        break;
                    }
                    // no break

                case Enclosure::ENCLOSURE_VIDEO:
                case Enclosure::ENCLOSURE_AUDIO:
                    if (($numImagesDone > 0) || ($numVideosDone > 0)) {
                        continue 2;
                    }

                    $numVideosDone++;

                    if (!in_array($mediaType, ['video/mp4', 'image/gif'])) {
                        continue 2;
                    }

                    if ($totalBytes > 15000000) {
                        continue 2;
                    }

                    break;

                default:
                    continue 2;
            }

            if ($myfile === null) {
                $myfile = @fopen($enclosure->url, 'rb');
                if ($myfile === false) {
                    continue;
                }
            }

            $apiUrl = 'https://upload.twitter.com/1.1/media/upload.json';
            $parameters = [
                'command' =>'INIT',
                'media_type' => $mediaType,
                'total_bytes' => strval($totalBytes),
            ];
            $mediaResult = $this->apiRequest($apiUrl, 'POST', $parameters, [], true);
            $mediaId = $mediaResult->media_id_string;

            $segmentIndex = 0;
            do {
                $bytes = fread($myfile, $segmentSize);

                $parameters = [
                    'command' => 'APPEND',
                    'media' => $bytes,
                    'media_id' => $mediaId,
                    'segment_index' => $segmentIndex,
                ];
                $this->apiRequest($apiUrl, 'POST', $parameters, [], true);

                $segmentIndex++;
            } while (!feof($myfile));

            fclose($myfile);

            $parameters = [
                'command' => 'FINALIZE',
                'media_id' => $mediaId,
            ];
            $this->apiRequest($apiUrl, 'POST', $parameters);

            $mediaIds[] = $mediaId;
        }

        @ini_set('allow_url_fopen', $allow_url_fopen);

        // Make request...

        $apiUrl = 'statuses/update.json';
        $parameters = [
            'status' => $status,
        ];
        if (!empty($mediaIds)) {
            $parameters['media_ids'] = implode(',', $mediaIds);
        }
        $result = $this->apiRequest($apiUrl, 'POST', $parameters);

        return $result->id_str;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAtom($identifier)
    {
        $apiUrl = 'statuses/destroy/' . $identifier . '.json';

        $this->apiRequest($apiUrl, 'POST');
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
        throw new NotImplementedException('There are no categories on Twitter.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCategory($identifier)
    {
        throw new NotImplementedException('There are no categories on Twitter.');
    }
}
