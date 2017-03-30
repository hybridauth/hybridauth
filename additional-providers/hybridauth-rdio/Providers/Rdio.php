<?php

/**
 * Hybrid_Providers_Rdio provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Rdio extends Hybrid_Provider_Model_OAuth2
{
    public static $_mediator = null;

    /**
     * IDp wrappers initializer
     */
    function initialize()
    {
        parent::initialize();

        $this->api->api_base_url = 'https://services.rdio.com/api/1/';
        $this->api->authorize_url = 'https://www.rdio.com/oauth2/authorize';
        $this->api->token_url = 'https://www.rdio.com/oauth2/token';

        $this->api->curl_authenticate_method = "POST";
    }

    /**
     * Request method
     * @param $url
     * @return mixed
     */
    function request($method, $params = [])
    {
        $url = $this->api->api_base_url . '?access_token=' . $this->api->access_token;
        $query = http_build_query(array_merge(['method' => $method], $params));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->api->curl_useragent);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data);
        return $data;
    }

    /**
     * Get current user info
     * @return mixed
     */
    function getUser()
    {
        $currentUser = $this->request('currentUser');

        if (isset($currentUser->result))
        {
            $currentUser = $currentUser->result;
        }
        else
        {
            $currentUser = null;
        }

        return $currentUser;
    }

    /**
     * Get favourite artists
     * @return mixed
     */
    function getFavouriteArtists($user_key)
    {
        $artists = $this->request('getFavorites', ['user' => $user_key, 'type' => 'artists']);

        if (isset($artists->result))
        {
            $artists = $artists->result;
        }
        else
        {
            $artists = null;
        }

        return $artists;
    }

    /**
     * Returns recent stations history
     * @param $user_key
     * @return mixed|null
     */
    function getRecentStationsHistoryForUser($user_key)
    {
        $stations = $this->request('getRecentStationsHistoryForUser', ['user' => $user_key]);

        if (isset($stations->result))
        {
            $stations = $stations->result;
        }
        else
        {
            $stations = null;
        }

        return $stations;
    }

    /**
     * Returns albums
     * @param $user_key
     * @return mixed|null
     */
    function getAlbumsInCollection($user_key)
    {
        $albums = $this->request('getAlbumsInCollection', ['user' => $user_key]);

        if (isset($albums->result))
        {
            $albums = $albums->result;
        }
        else
        {
            $albums = null;
        }

        return $albums;
    }

    /**
     * Returns user's playlists
     * @param $user_key
     * @return mixed|null
     */
    function getUserPlaylists($user_key)
    {
        $playlists = $this->request('getUserPlaylists', ['user' => $user_key, 'kind' => 'owned']);

        if (isset($playlists->result))
        {
            $playlists = $playlists->result;
        }
        else
        {
            $playlists = null;
        }

        return $playlists;
    }

    /**
     * Returns user's friends
     * @param $user_key
     * @return mixed|null
     */
    function getUserFriends($user_key)
    {
        $friends = $this->request('userFollowing', ['user' => $user_key]);

        if (isset($friends->result))
        {
            $friends = $friends->result;
        }
        else
        {
            $friends = null;
        }

        return $friends;
    }
}