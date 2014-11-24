<?php

/**
 * Hybrid_Providers_BeatsMusic - BeatsMusic provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_BeatsMusic extends Hybrid_Provider_Model_OAuth2
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

        $this->api->api_base_url = 'https://partner.api.beatsmusic.com/';
        $this->api->authorize_url = 'https://partner.api.beatsmusic.com/v1/oauth2/authorize';
        $this->api->token_url = 'https://partner.api.beatsmusic.com/v1/oauth2/token';

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
        curl_setopt($ch, CURLOPT_URL, $this->api->api_base_url . 'v1/api/' . $url);
        $headers = array('Authorization: Bearer ' . $this->api->access_token);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data, true);
        return $data;
    }

    /**
     * Returns user`s ID
     * @return null|string
     */
    function getUserId()
    {
        $user_id = null;
        $result = $this->request('me');
        if (is_array($result) && isset($result['result']))
        {
            $result = $result['result'];
            $user_id = $result['user_context'];
        }

        return $user_id;
    }

    /**
     * Returns user`s artists list
     * @param $user_id
     * @return mixed
     */
    function getUsersArtists($user_id, $limit = 150, $offset = 0)
    {
        return $this->request('users/' . $user_id . '/mymusic/artists', ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Returns full user`s artists list
     * @param $user_id
     * @return mixed
     */
    function getUsersArtistsFullList($user_id)
    {
        $offset = 0;
        $limit = 150;
        $artists = [];
        do
        {
            $result = $this->request('users/' . $user_id . '/mymusic/artists', ['limit' => $limit, 'offset' => $offset]);
            if (!empty($result['data']))
            {
                $artists = array_merge($artists, $result['data']);
            }
            $offset += $limit;
        } while ($result['info']['total'] > $offset);

        return $artists;
    }
}
