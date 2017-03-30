<?php

/**
 * Hybrid_Providers_MixCloud - MixCloud provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_MixCloud extends Hybrid_Provider_Model_OAuth2
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

        $this->api->api_base_url = 'https://api.mixcloud.com';
        $this->api->authorize_url = 'https://www.mixcloud.com/oauth/authorize';
        $this->api->token_url = 'https://www.mixcloud.com/oauth/access_token';

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
     * @param $url endpoint relative URL
     * @param null $params any request params
     * @param bool|false $metadata Get metadata from API
     * @return mixed
     */
    function request($url, $params = null, $metadata = false)
    {
        $ch = curl_init();

        $url = $url . '?access_token=' . $this->api->access_token;

        if ($metadata)
        {
            $params['metadata'] = 1;
        }

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
     * Returns user`s data
     * @param bool|false $metadata
     * @return mixed
     */
    function getUser($metadata = false)
    {
        $result = $this->request('me/', null, $metadata);
        return $result;
    }
}
