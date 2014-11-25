<?php

/**
 * Hybrid_Providers_SoundCloud - SoundCloud provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_SoundCloud extends Hybrid_Provider_Model_OAuth2
{
    // default permissions
    public $scope = "";

    public static $_profileData = null;

    /**
     * Initializer
     */
    function initialize()
    {
        parent::initialize();

        $this->api->api_base_url = 'https://api.soundcloud.com';
        $this->api->authorize_url = 'https://api.soundcloud.com/connect';
        $this->api->token_url = 'https://api.soundcloud.com/oauth2/token';

        $this->api->curl_authenticate_method = "POST";
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

        $url = $url . '?oauth_token=' . $this->api->access_token;
        if ($params)
        {
            $url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query($params, '', '&');
        }
        $url = $this->api->api_base_url . '/' . $url;

        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data, true);
        return $data;
    }

    /**
     * Returns user`s ID
     * @return null|string
     */
    function getUser()
    {
        $user_id = null;
        $result = $this->request('me.json');
        return $result;
    }

    /**
     * Returns followings
     * @param $user_id
     * @return mixed
     */
    function getUsersFollowings($user_id = 'me')
    {
        return $this->request('users/' . $user_id . '/followings.json');
    }
}
