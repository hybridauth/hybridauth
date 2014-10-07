<?php
// https://raw.github.com/fsobczak/PHP-LastFm-Minimal-API/master/LastFM.php
// modified 

/*
 * This is minimal PHP library - it implements all the necessary
 * stuff, and ONLY that.
 * 
 * Implemented:
 * - authentication flow
 * - api calls wrapper
 * - error wrapper
 */ 

/**
 * Thrown when an API call returns an exception.
 *
 * @author Filip Sobczak <f@digitalinvaders.pl>
 */
class LastFMException extends Exception {

    /**
     * The result from the API server that represents the exception information.
     */
    protected $result;

    /**
     * Make a new API Exception with the given result.
     *
     * @param Array $result the result from the API server
     */
    public function __construct($result) {
        $this->result = $result;

        $code = isset($result['error']) ? $result['error'] : 0;

        if (isset($result['message'])) {
            $msg = $result['message'];
        } else {
            $msg = 'Unknown Error. Check getResult()';
        }

        parent::__construct($msg, $code);
    }

    /**
     * Return the associated result object returned by the API server.
     *
     * @returns Array the result from the API server
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * To make debugging easier.
     *
     * @returns String the string representation of the error
     */
    public function __toString() {
        $str = '';
        if ($this->code != 0) {
            $str .= $this->code . ': ';
        }
        return $str . $this->message;
    } 

}
    
class LastFMInvalidSessionException extends LastFMException {
    public function __construct($result) {
        parent::__construct($result); 
    }
}

/**
 * Provides access to the LastFM platform.
 *
 * @author Filip Sobczak <f@digitalinvaders.pl>
 */
class LastFM {
    const VERSION = '0.9';

    /**
     * Default options for curl.
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'lastfm-php-0.9',
    );
    /**
     * The Application API Secret.
     */
    protected $apiSecret;
    /**
     * The Application API Key.
     */
    protected $apiKey;
    /**
     * The active user session key, if one is available.
     */
    protected $sk;
    public static $DOMAIN_MAP = array(
        'www' => 'https://www.last.fm/',
        'webservice' => 'https://ws.audioscrobbler.com/2.0/',
    );

    const method_auth = 1;
    const method_write = 2;
    const method_get_auth = 3;
    const method_unknown = 4;

    /*
     * Some methods require authentication (type auth),
     * they all send api_sig and sk
     * some methods are used to get authenticated (type get_auth)
     * they all send api_sig
     * some methods are used to write data (type write)
     * they all send api_sig and sk, and use POST http method
     * 
     * All letters are small because users might use 
     * variations of letter sizes, and we need to 
     * find these values fast, so strtolower is executed on method name.
     */
    public static $METHOD_TYPE =
            array(
        'auth.getmobilesession' => self::method_get_auth,
        'auth.getsession' => self::method_get_auth,
        'auth.gettoken' => self::method_get_auth,
        'album.addtags' => self::method_write,
        'album.gettags' => self::method_auth,
        'album.removetag' => self::method_write,
        'album.share' => self::method_write,
        'artist.addtags' => self::method_write,
        'artist.gettags' => self::method_auth,
        'artist.removetag' => self::method_write,
        'artist.share' => self::method_write,
        'artist.shout' => self::method_write,
        'event.attend' => self::method_write,
        'event.share' => self::method_write,
        'event.shout' => self::method_write,
        'library.addalbum' => self::method_write,
        'library.addartist' => self::method_write,
        'library.addtrack' => self::method_write,
        'library.removealbum' => self::method_write,
        'library.removeartist' => self::method_write,
        'library.removescrobble' => self::method_write,
        'library.removetrack' => self::method_write,
        'playlist.addtrack' => self::method_write,
        'playlist.create' => self::method_write,
        'radio.getplaylist' => self::method_auth,
        'radio.tune' => self::method_write,
        'track.addtags' => self::method_write,
        'track.ban' => self::method_write,
        'track.gettags' => self::method_auth,
        'track.love' => self::method_write,
        'track.removetag' => self::method_write,
        'track.scrobble' => self::method_write,
        'track.share' => self::method_write,
        'track.unban' => self::method_write,
        'track.unlove' => self::method_write,
        'track.updatenowplaying' => self::method_write,
        'user.getrecentstations' => self::method_auth,
        'user.getrecommendedartists' => self::method_auth,
        'user.getrecommendedevents' => self::method_auth,
        'user.shout' => self::method_write,
    );

    /**
     * Initialize LastFM application.
     * 
     * @param type $config configuration
     */
    public function __construct($config) {
//$this->setAppId($config['appId']);
        $this->setApiKey($config['api_key']);
        $this->setApiSecret($config['api_secret']);
        if (isset($config['sk'])) {
            $this->setSessionKey($config['sk']);
        }
    }

    public function setApiSecret($apiSecret) {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    public function getApiSecret() {
        return $this->apiSecret;
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function setSessionKey($sk) {
        $this->sk = $sk;
        return $this;
    }

    public function getSessionKey() {
        return $this->sk;
    }

    private function methodType($method) {
        if (isset(self::$METHOD_TYPE[strtolower($method)])) {
            return self::$METHOD_TYPE[strtolower($method)];
        } else {
            return self::method_unknown;
        }
    }

    /**
     * Get a Login URL for use with redirects.
     *
     * The parameters:
     * - api_key: application api key
     *
     * @param Array $callback override default redirect
     * @return String the URL for the login flow
     */
    public function getLoginUrl($callback=array()) {
        $params = array('api_key' => $this->getApiKey());
        if ($callback)
            $params['cb'] = $callback;
        return $this->getUrl('www', 'api/auth', $params);
    }

    /**
     * @param type $token 32-char ASCII MD5 hash, gained by granting permissions
     */
    public function fetchSession($token = '') {
        if (!$token) {
            if (isset($_GET['token']))
                $token = $_GET['token'];
        }
        $result = $this->api('auth.getSession', array('token' => $token));
        //print_r($result);
        //print_r($result); print_r($result['session']['key']); exit;
        $name = $result['session']['name'];
        $sessionKey = $result['session']['key'];
        $this->setSessionKey($sessionKey);
        
        return array('name' => $name, 'sk' => $sessionKey);
    }

    /**
     * Make an API call
     *
     * @param Array $params method call object
     * @return the decoded response object
     * @throws LastFMApiException
     */
    public function api($method, $params = array()) {
        // generic application level parameters
        $params['api_key'] = $this->getApiKey();
        $params['format'] = 'json';

        // required api method
        $params['method'] = $method;

        if ($this->methodType($method))
            $methodType = $this->methodType($method);



        if ($methodType == self::method_auth || $methodType == self::method_write) {
            if (!isset($params['sk'])) {
                $params['sk'] = $this->getSessionKey();
            }
            if (!$params['sk']) {
                throw new LastFMException(array("message" => "No session key provided"));
            }
        } else {
            if (isset($params['sk']))
                unset($params['sk']);
        }

        if ($methodType == self::method_get_auth || $methodType == self::method_write) {
            $params['api_sig'] = $this->generateSignature($params);
        }

        $raw = $this->makeRequest(self::getUrl('webservice'), $params);
        $result = json_decode($raw, true);

        if (is_array($result) && isset($result['error'])) {
            if ($result['error'] == 9) {
                // Invalid session key - Please re-authenticate
                // this is different so that when user invalidates
                // session the situation can be handled easily
                throw new LastFMInvalidSessionException($result);
            } else
                throw new LastFMException($result);
        }
        return $result;
    }

    /**
     * Makes an HTTP request.
     *
     * @param String $url the URL to make the request to
     * @param Array $params the parameters to use for the POST body
     * @return String the response text
     */
    protected function makeRequest($url, $params) {

        $ch = curl_init();
        $opts = self::$CURL_OPTS;
        $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
        $opts[CURLOPT_URL] = $url;

        curl_setopt_array($ch, $opts);
		
		// mod:by:me
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
        $result = curl_exec($ch);

        if ($result === false) {
            $e = new LastFMException(array(
                        'error' => curl_errno($ch),
                        'message' => curl_error($ch),
                    ));
            curl_close($ch);
            throw $e;
        }

        curl_close($ch);
        return $result;
    }

    /**
     * Build the URL for given domain alias, path and parameters.
     *
     * @param $name String the name of the domain
     * @param $path String optional path (without a leading slash)
     * @param $params Array optional query parameters
     * @return String the URL for the given parameters
     */
    protected function getUrl($name, $path='', $params=array()) {
        $url = self::$DOMAIN_MAP[$name];
        if ($path) {
            if ($path[0] === '/') {
                $path = substr($path, 1);
            }
            $url .= $path;
        }
        if ($params) {
            $url .= '?' . http_build_query($params, null, '&');
        }
        return $url;
    }

    /**
     * Generate a signature for the given params and secret.
     *
     * @param Array $params the parameters to sign
     * @return String the generated signature
     */
    protected function generateSignature($params) {
        // work with sorted data
        ksort($params);

        $base_string = '';
        foreach ($params as $key => $value) {
            if ($key == 'format' || $key == 'callback')
                continue;
            $base_string .= $key . $value;
        }
        $base_string .= $this->getApiSecret();
        return md5(utf8_encode($base_string));
    }

}
