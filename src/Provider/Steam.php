<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;

/**
 * Steam OpenID provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'     => [ 'secret' => 'steam-api-key' ]
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Steam( $config );
 *
 *   $adapter->authenticate();

 *   $userProfile = $adapter->getUserProfile();
 */
class Steam extends OpenID {
    /**
     * {@inheritdoc}
     */
    protected $openidIdentifier = 'https://steamcommunity.com/openid';

    /**
     * @var string User profile URL template use in sprintf().
     */
    protected $profileUrlTemplate = 'https://steamcommunity.com/profiles/%s/';

    /**
     * {@inheritdoc}
     */
    public function authenticateFinish() {
        parent::authenticateFinish();

        $userProfile = $this->storage->get( $this->providerId . '.user' );

        if ( !preg_match( '/\/(\d{1,})$/m', $userProfile->identifier, $matches ) ) {
            throw new UnexpectedApiResponseException( 'Provider API returned an unexpected response.' );
        }

        $userProfile->identifier = trim( $matches[1] );

        try {
            $apiKey = $this->config->filter( 'keys' )->get( 'secret' );

            // if api key is provided, we attempt to use steam web api
            if ( $apiKey ) {
                $result = $this->getUserProfileWebAPI( $apiKey, $userProfile->identifier );
            } else {
                $result = $this->getUserProfileLegacyAPI( $userProfile->identifier );
            }

            // fetch user profile
            foreach ( $result as $k => $v ) {
                $userProfile->$k = $v ?: $userProfile->$k;
            }
        } // these data are not mandatory, so keep it quite
        catch ( \Exception $e ) {
        }

        // store user profile
        $this->storage->set( $this->providerId . '.user', $userProfile );
    }

    /**
     * Fetch user profile on Steam web API
     *
     * @return array User data.
     */
    public function getUserProfileWebAPI( $apiKey, $steam64 ) {
        $apiUrl = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $apiKey . '&steamids=' . $steam64;

        $response = $this->httpClient->request( $apiUrl );

        $data = json_decode( $response );

        $data = isset( $data->response->players[0] ) ? $data->response->players[0] : null;

        $data = new Data\Collection( $data );

        $userProfile = [];

        $profileUrl = (string) $data->get( 'profileurl' );

        if ( empty( $profileUrl ) ) {
            $profileUrl = sprintf( $this->profileUrlTemplate, $steam64 );
        }

        $userProfile['displayName'] = (string) $data->get( 'personaname' );
        $userProfile['firstName']   = (string) $data->get( 'realname' );
        $userProfile['photoURL']    = (string) $data->get( 'avatarfull' );
        $userProfile['profileURL']  = $profileUrl;
        $userProfile['country']     = (string) $data->get( 'loccountrycode' );

        return $userProfile;
    }

    /**
     * Fetch user profile on community API
     *
     * @param string $steam64 Steam ID, e.g. 76561198123452.
     *
     * @return array User data.
     */
    public function getUserProfileLegacyAPI( $steam64 ) {
        libxml_use_internal_errors( false );

        $apiUrl = 'http://steamcommunity.com/profiles/' . $steam64 . '/?xml=1';

        $response = $this->httpClient->request( $apiUrl );

        $data = new \SimpleXMLElement( $response );

        $data = new Data\Collection( $data );

        $userProfile = [];

        $profileUrl = (string) $data->get( 'customURL' );

        if ( empty( $profileUrl ) ) {
            $profileUrl = sprintf( $this->profileUrlTemplate, $steam64 );
        }

        $userProfile['displayName'] = (string) $data->get( 'steamID' );
        $userProfile['firstName']   = (string) $data->get( 'realname' );
        $userProfile['photoURL']    = (string) $data->get( 'avatarFull' );
        $userProfile['description'] = (string) $data->get( 'summary' );
        $userProfile['region']      = (string) $data->get( 'location' );
        $userProfile['profileURL']  = $profileUrl;

        return $userProfile;
    }
}
