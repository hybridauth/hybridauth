<?php
require_once dirname(__FILE__) . '/../thirdparty/Rdio/rdio.php';

/**
 * Hybrid_Providers_Rdio provider adapter based on OAuth1 protocol
 */
class Hybrid_Providers_Rdio extends Hybrid_Provider_Model_OAuth1
{
    public static $_mediator = null;

    /**
     * IDp wrappers initializer
     */
    function initialize()
    {
        parent::initialize();

        // Provider api end-points
        $this->api->api_base_url      = 'http://api.rdio.com/1/';
        $this->api->authorize_url     = 'https://www.rdio.com/oauth/authorize';
        $this->api->request_token_url = 'http://api.rdio.com/oauth/request_token';
        $this->api->access_token_url  = 'http://api.rdio.com/oauth/access_token';

        $this->api->curl_authenticate_method = "POST";
    }

    /**
     * Get third-party mediator for API calls that generate correct signatures for requests
     * @return Rdio
     */
    function getMediator()
    {
        if (empty(self::$_mediator))
        {
            $rdio = new Rdio([$this->config['keys']['key'], $this->config['keys']['secret']]);
            $rdio->token = array($this->api->token->key, $this->api->token->secret);
            self::$_mediator = $rdio;
        }

        return self::$_mediator;
    }

    /**
     * Request method
     * @param $url
     * @return mixed
     */
    function request($url, $params = null)
    {
        $mediator = $this->getMediator();
        $data = $mediator->call($url, $params);
        return $data;
    }

    /**
     * Get current user info
     * @return mixed
     */
    function getUser()
    {
        $currentUser = $this->request('currentUser');
        return $currentUser;
    }

    /**
     * Get favourite artists
     * @return mixed
     */
    function getFavouriteArtists()
    {
        $artists = $this->request('getFavorites', ['type' => 'artists']);
        return $artists;
    }
}
