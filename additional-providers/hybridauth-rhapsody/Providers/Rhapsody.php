<?php

/**
 * Hybrid_Providers_Rhapsody - Rhapsody provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Rhapsody extends Hybrid_Provider_Model_OAuth2
{
    /**
     * Initializer
     */
    function initialize()
    {
        parent::initialize();

        $this->api->api_base_url = 'https://api.rhapsody.com/';
        $this->api->authorize_url = 'https://api.rhapsody.com/oauth/authorize';
        $this->api->token_url = 'https://api.rhapsody.com/oauth/access_token';

        $this->api->curl_authenticate_method = "POST";

        $this->api->curl_useragent = "CWM";
    }

    /**
     * Begin login step
     */
    function loginBegin()
    {
        // redirect the user to the provider authentication url
        Hybrid_Auth::redirect($this->api->authorizeUrl());
    }

    /**
     * Request method with Bearer access_token auth
     * @param $url
     * @return mixed
     */
    function request($url, $params = null)
    {
        $ch = curl_init();

        if ($params)
        {
            $url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query($params, '', '&');
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->api->curl_useragent);
        curl_setopt($ch, CURLOPT_URL, $this->api->api_base_url . 'v1/' . $url);
        $headers = array('Authorization: Bearer ' . $this->api->access_token);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data, true);
        return $data;
    }

    /**
     * Returns artists from library
     * @return null|string
     */
    function getArtists()
    {
        $result = $this->request('me/library/artists');
        return $result;
    }

    /**
     * Returns albums from library
     * @return null|string
     */
    function getAlbums()
    {
        $result = $this->request('me/library/albums');
        return $result;
    }

    /**
     * Returns favourite tracks
     * @return null|string
     */
    function getFavourites()
    {
        $result = $this->request('me/favorites');
        return $result;
    }

    /**
     * Returns user`s playlists
     * @param $user_id
     * @return mixed
     */
    function getPlaylists()
    {
        $result = $this->request('me/playlists');
        return $result;
    }

    /**
     * Returns playlist artists
     * @param $user_id
     * @return mixed
     */
    function getPlaylistTracks($playlist_id)
    {
        $result = $this->request('me/playlists/' . $playlist_id . '/tracks');
        return $result;
    }
}
