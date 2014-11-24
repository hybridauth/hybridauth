<?php

/**
 * Hybrid_Providers_Deezer - Deezer provider adapter based on OAuth2 protocol
 */
class Hybrid_Providers_Deezer extends Hybrid_Provider_Model_OAuth2
{
    // default permissions
    public $scope = "basic_access,email,offline_access";

    public static $_profileData = null;


    /**
     * IDp wrappers initializer
     */
    function initialize()
    {
        parent::initialize();

        $this->api->api_base_url = 'https://api.deezer.com/';
        $this->api->authorize_url = 'https://connect.deezer.com/oauth/auth.php';
        $this->api->token_url = 'https://connect.deezer.com/oauth/access_token.php';

        $this->api->curl_authenticate_method = "GET";
    }

    public function getUserProfile()
    {
        if (self::$_profileData === null)
        {
            $data = $this->request('user/me');
            self::$_profileData = json_decode($data, true);
        }
        return self::$_profileData;
    }

    public function getUserId()
    {
        $data = $this->getUserProfile();
        $id = $data['id'];
        return $id;
    }

    public function getUserArtists()
    {
        $data = $this->request('user/' . $this->getUserId() . '/artists');
        $data = json_decode($data, true);
        while (isset($data['next']))
        {
            $tempData = $this->request($data['next']);
            $tempData = json_decode($tempData, true);
            unset($data['next']);
            if (isset($tempData['next']))
            {
                $data['next'] = $tempData['next'];
            }
            $data['data'] = array_merge($data['data'], $tempData['data']);
        }
        return $data;
    }

    public function getUserAlbums()
    {
        $data = $this->request('user/' . $this->getUserId() . '/albums');
        $data = json_decode($data, true);
        while (isset($data['next']))
        {
            $tempData = $this->request($data['next']);
            $tempData = json_decode($tempData, true);
            unset($data['next']);
            if (isset($tempData['next']))
            {
                $data['next'] = $tempData['next'];
            }
            $data['data'] = array_merge($data['data'], $tempData['data']);
        }
        return $data;
    }

    public function getUserFriends()
    {
        $data = $this->request('user/' . $this->getUserId() . '/followings');
        $data = json_decode($data, true);
        while (isset($data['next']))
        {
            $tempData = $this->request($data['next']);
            $tempData = json_decode($tempData, true);
            unset($data['next']);
            if (isset($tempData['next']))
            {
                $data['next'] = $tempData['next'];
            }
            $data['data'] = array_merge($data['data'], $tempData['data']);
        }
        return $data;
    }

    public function request($method, $params = [])
    {
        try
        {
            if (preg_match("/https:\/\//", $method)) {
                $url = $method . '?access_token=' . $this->api->access_token;
            }
            else
            {
                $url = $this->api->api_base_url . $method . '?access_token=' . $this->api->access_token . '&limit=10000';
            }
            $data = $this->simpleRequest($url);
        } catch (Exception $e)
        {
            $data = ['error' => "Can't provide query"];
        }
        return $data;
    }

    /**
     * begin login step
     */
    function loginBegin()
    {
        // redirect the user to the provider authentication url
        Hybrid_Auth::redirect($this->api->authorizeUrl(array("perms" => $this->scope)));
    }

    /**
     * @param string $url URL
     * @return mixed
     */
    function simpleRequest($url, $check_code = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CWM UserAgent');
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($check_code && $info['http_code'] != 200)
        {
            return null;
        }

        return $data;
    }


}
