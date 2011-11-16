<?php
// http://dev.viadeo.com/documentation/tools-and-samples/php-sdk/
// modi:ssl ver

// ============================================================================
// Viadeo Graph API - PHP Software Development Kit
//
// The Viadeo API team <api-support@viadeo.com>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// ============================================================================

$VIADEOAPI_VERSION = "0.2.2";

// == VIADEO API EXCEPTIONS ===================================================

class ViadeoException extends Exception { }
class ViadeoSDKException extends ViadeoException { }
class ViadeoInvalidConfigurationException extends ViadeoException { }
class ViadeoOAuth2Exception extends ViadeoException { }
class ViadeoAuthenticationException extends ViadeoException { }
class ViadeoAPIException extends ViadeoException { }
class ViadeoConnectionException extends ViadeoException { }

// == THE VIADEO API REQUEST CLASS ============================================
//
//
// After creating a ViadeoAPI instance, for instance using (w/ access token):
//        $VD = new ViadeoAPI('abcdef42ghijkl42mnopqr42stuvwxyz');
//
// You can request for a ViadeoRequest instance using :
//        $req = $VD->id('abcdef42ghijkl');  // a graph object id
//        $req = $VD->get('/me');
//        $req = $VD->post('/status');
//        $req = $VD->put('/abcdef42ghijkl');
//        $req = $VD->del('/abcdef42ghijkl');
//
// Or directly using the constructor :
//        $req = new ViadeoRequest($VD, '/me');
//        $req = new ViadeoRequest($VD, '/status', 'POST');
//
// Once created a request can be manipulated :
//
//  ** reset the request for a new usage (all is defaulted, ViadeoAPI is kept)
//        $req->reset();
//
//  ** set the request path :
//        $req->setPath('/me/contacts');
//
//  ** set a complete Viadeo API URL (limited on domain setting to Viadeo) :
//        $req->setURL('https://api.viadeo.com/me?user_detail=partial');
//
//  ** add a connection (mainly used when created the request with $VD->id())
//        $req = $VD->id('abcdef42ghijkl')->connection('contacts')
//        print $req->getPath();
//        >> "/abcdef42ghijkl/contacts"
//
//  ** set the HTTP method
//        $req = $VD->id('abcdef42ghijkl'); // id of a removable item
//        $req.setMethod("DELETE");         // prepare for removal using
//                                          // the same request instance
//
//  ** set parameters (every unknown method call is mapped to setParam())
//       // Prepare a search request for users with name 'loic dias da sila'
//       $req = $VD->get('/search/users')->name('loic dias da silva');
//       // Add another parameter, on a new line, setting search results limit
//       $req.limit('50')
//       // Another way to set parameter, through setParam()
//       $req.setParam('user_detail', 'partial')
//
//  ** then execute the request :
//       $result = $req->execute();
//       $result = $req->x();
//       $result = $req();
//
//  ** you can retrieve informations about the request :
//
//       $req-getPath();        // Get the path (aka: '/me', '/status', ...)
//                              // Return null if setURL() was called before
//       $req->getFullPath();   // Compute the callable Viadeo API URI
//       $req->getMethod();     // The HTTP method (aka. 'GET', 'POST', ...)
//       $req->getParams();     // The url encoded parameters
//
// ============================================================================

class ViadeoRequest {

    private $api;      // Used to store the ViadeoAPI linked instance

    private $path;     // The API path ('/me', '/status', '/<user>/contacts', )
    private $params;   // The parameters of the request
    private $method;   // The HTTP method to be used

    // -- Initialization ------------------------------------------------------
    function __construct($api, $path, $method = "GET") {
        $this->reset();
        $this->api = $api;
        $this->setPath($path);
        $this->method = $method;
    }

    public function reset() {
        $this->path = null;
        $this->params = array();
        $this->method = "GET";
    }

    // -- URI/Path management -------------------------------------------------
    public function setPath($path) {
        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }
        $this->path = $path;
        return $this;
    }

    public function setURL($url) {
        # FIXME: waiting for API correction on paging links
        #if (stripos($this->rawURL, ViadeoAPI::$api_base, 0) != 0) {
        #    throw new ViadeoSDKException("You cannot override API base");
        #}

        $obj_url = parse_url($url);
        $this->path = $obj_url['path'];

        parse_str($obj_url['query'], $queryArr);
        $this->params = array_merge($this->params, $queryArr);

        return $this;
    }

    public function connection($connection) {
        $connection = preg_replace('/^\/*(.*)\/*?$/', '$1', $connection);
        $this->path .= '/' . $connection;
        return $this;
    }

    public function getPath() {
        return ViadeoAPI::$api_base . $this->path;
    }

    public function getFullPath($extras = array()) {
        $path = $this->getPath();
        if ((count($this->params) > 0) || (count($extras) > 0)) {
            $path .= "?" . $this->getParams($extras);
        }
        return $path;
    }

    public function getFullPathWithToken($extras = array()) {
        return $this->getFullPath(array('access_token' => $this->api->getAccessToken()));
    }

    // -- HTTP Method management ----------------------------------------------
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    public function getMethod() {
        return $this->method;
    }

    // -- Parameters management -----------------------------------------------
    public function getParams($extras = array(), $json = false) {
        $params = "";
        if ((count($this->params) > 0) || (count($extras) > 0)) {
            if ( ! $json ) {
                $params = http_build_query(array_merge($this->params, $extras), null, '&');
            } else {
                $params = json_encode(array_merge($this->params, $extras));
            }
        }
        return $params;
    }

    public function setParam($name, $value) {
        $this->params[$name] = $value;
        return $this;
    }

    public function __call($name, $arguments) {
        $value = null;

        if (count($arguments) == 0) {
            $value = 'true';
        } else if (count($arguments) > 1) {
            throw new ViadeoIllegalArgumentException();
        } else {
            $value = $arguments[0];
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
        }

        $this->params[$name] = $value;
        return $this;
    }

    // -- Execute the query ---------------------------------------------------    
    public function execute() {
        return $this->api->execute($this);
    }

    public function x() {
        return $this->execute();
    }

    public function __invoke() {
        return $this->execute();
    }

}

// == THE VIADEO GRAPH OBJECT CLASS ===========================================
//
// After executing a request :
//
//     $me = $VD->get('/me').x();
//     $contacts = $VD->id('me')->connection('contacts')->x();
//
// You obtain a ViadeoGraphObject if the result contains the 'id' property.
//
// You can then use it to retrieve its properties :
//
//     $name = $me->name;
//
// If the property is also an object, you get another ViadeoGraphObject instance :
//
//     $firstcontact = $contacts->data[0];
//     $name = $firstcontact->name;
//
// You can execute a new request using a ViadeoGraphObject instance :
//
//     $req = $obj->connection($connection);
//     $req = $obj->get();
//     $req = $obj->put();
//     $req = $obj->del();
//
//     ex:
//
//     // get my contacts
//     $contacts = $me->connection('contacts')->x();
//
//     // retrieve all my data
//     $fullme = $me->get()->user_detail('full')->x();
//
//     // update my interests
//     $me->put()->interests($me->interests . ", Coding")->x();
//     
// ============================================================================
class ViadeoGraphObject {

    private $api;
    private $data;

    // -- Initialization ------------------------------------------------------

    function __construct($api, $data) {
        $this->api = $api;
        $this->data = $data;
    }

    private function req() {
        return $this->api->id($this->data->id);
    }

    // -- Request builders ----------------------------------------------------

    public function connection($connection) {
        return $this->req()->connection($connection);
    }

    public function get() {
        return $this->req();
    }

    public function del() {
        return $this->req()->setMethod('DELETE');
    }

    public function put() {
        return $this->req()->setMethod('PUT');
    }

    // -- Get object properties -----------------------------------------------

    public function __get($name) {
        if (isset($this->data->$name)) {
            $data = $this->data->$name;
            if (isset($data->id)) {
                return new ViadeoGraphObject($this->api, $data);
            }
            if (is_array($data) && (count($data) > 0) && isset($data[0]->id)) {
                $newdata = array();
                foreach ($data as $item) {
                    $newdata[] = new ViadeoGraphObject($this->api, $item);
                }
                return $newdata;
            }
            return $data;
        }
        return null;
    }

    public function __isset($name) {
        return isset($this->data->$name);
    }

}

// == THE VIADEO API CLASS ====================================================
//
// You can create an instance in two ways :
//
//      // Empty instance, needs authentication
//      $VD = new ViadeoAPI();
//
//           or
//
//      // With an access token, authentication is done
//      $AT = "abcdef42ghijkl42mnopqr42stuvwxyz";
//      $VD = new ViadeoAPI($AT);
//
// You can also specify some options :
//
//      $VD->init(array(
//        'client_id'        =>    'CLIENTID',
//        'client_secret'    =>    'CLIENTSECRE'
//      ));
//
//      $VD->setOption('store', true);
//
// Available options are :
//
//      - client_id     (OAuth 2.0 - mandatory for authentication)
//      - client_secret (OAuth 2.0 - mandatory for authentication)
//      - access_token  (other way to specify access_token)
//      - store (bool)  (enable/disable access_token storing into cookie)
//
// You can also specify cURL options to be used during connections :
//
//      $VD->setCurlOption(CURLOPT_TIMEOUT, 10);
//
// OAuth 2.0 Connection management :
//
//      $VD->isAuthenticated();                  // True if access_token is set
//
//      $VD->disconnect();                       // if access_token storage is activated
//                                               // delete the cookie
//
//      $VD->(set/get)AccessToken($AT);          // The Viadeo API Acccess Token
//
//      $VD->(set/get)AuthorizationCode($AC);    // The OAuth2.0 step 1 code)
//                                               // Try to get from $_REQUEST if not set
//
//      $VD->(set/get)RedirectURI('http://...'); // Defaulted to current script URI
//
// OAuth 2.0 - step 1 :
//
//      $VD->getAuthorizationURL();      // Return the URL for user redirection
//      $VD->getAuthorizationURLPopup(); // Same thing but with popup layout
//      $VD-authorize();                 // Helper, redirects user to the getAuthorizationURL()
//                                       // Send header('Location')
//
// OAuth 2.0 - step 2 :
//
//      $VD->setAccessTokenFromCode();   // Use the step 1 code (getAuthorizationCode())
//                                       // in order to fill-in the access token from cURL
//
// OAuth helper :
//
//      $VD->OAuth_auto();               // Automatically runs all the OAuth 2.0 workflow
//                                       // on main page
//      ex :
//
//      // insert here $VD initialization, setting client_id and client_secret
//      try { $VD->OAuth_auto(); } catch (ViadeoException $e)  {
//          echo "An error occured during Viadeo API authentication: $e";
//      }
//      // insert here API calls, ex: $me = $VD->get('me')->execute();
//
// Execute a ViadeoRequest :
//
//      $res = $VD->execute($req);
//
// ============================================================================

class ViadeoAPI {

    private $authorization_code;  // OAuth 2.0 - The authorization code
    private $redirect_uri;        // OAuth 2.0 - The redirection URI
    private $access_token;        // OAuth 2.0 - The Access Token for API calls
    private $config;              // The Viadeo API configuration

    // -- Static URIs ---------------------------------------------------------
    public static $api_base      = "https://api.viadeo.com";
    public static $authorize_url = "https://secure.viadeo.com/oauth-provider/authorize2";
    public static $token_url     = "https://secure.viadeo.com/oauth-provider/access_token2";

    // -- Default CURL options ------------------------------------------------
    private $curl_opts = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HEADER         => TRUE,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => "viadeo-api-php-sdk-agent", // FIXME: add version
        CURLOPT_HTTPHEADER     => array("Accept: application/json; charset=UTF-8")
    );

    // == Initialization / Configuration ======================================
    // ========================================================================
    function __construct($access_token = null) {
        $this->setAccessToken($access_token);
    }

    public function init($config) {
        $this->config = $config;
        return $this;
    }
 
    public function setOption($name, $value) {
        $this->config[$name] = $value;
    }

    private function getConfigKey($key, $mandatory = false) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        } else if ($mandatory) {
            throw new ViadeoInvalidConfigurationException(
                "Configuration key '".$key."' is missing");
        } else {
            return null;
        }
    }

    private function getCookieName() {
        $suffix = $this->getConfigKey('client_id');
        if ($suffix == null) {
            $suffix = "default";
        }
        return "vds_" . $suffix;
    }

    public function setCurlOption($key, $value) {
        $this->curl_opts[$key] = $value;
    }

    // == OAuth2 Authentication layer =========================================
    // ========================================================================

    // -- Access Token mgt ----------------------------------------------------
    public function isAuthenticated() {
        return ($this->getAccessToken() != null);
    }

    public function disconnect() {
        $this->access_token = null;
        if ($this->getConfigKey('store') === true) {
            setcookie($this->getCookieName(), "", time() - 3600);
            unset($_COOKIE[$this->getCookieName()]);
        }
        return $this;
    }

    public function setAccessToken($access_token) {
        $this->access_token = $access_token;
        if ($this->getConfigKey('store') === true) {
            setrawcookie($this->getCookieName(), 
                         '"access_token='.$access_token.'"', time() + 3600);
        }
        return $this;
    }

    public function getAccessToken() {
        $token = null;

        if (isset($this->access_token)) {
            $token = $this->access_token;

        } else if ($this->getConfigKey('access_token') != null) {
            $this->access_token = $this->getConfigKey('access_token');
            $token = $this->access_token;

        } else if ($this->getConfigKey('store') === true) {
            if (isset($_COOKIE[$this->getCookieName()])) {
                $cookVal = $_COOKIE[$this->getCookieName()];
                parse_str(str_replace('"', '', $cookVal), $cookArr);
                if (isset($cookArr['access_token'])) {
                    $this->access_token = $cookArr['access_token'];
                    $token = $this->access_token;
                }
            }
        }

        return $token;
    }

    // -- Authorization code --------------------------------------------------
    public function setAuthorizationCode($authorization) {
        $this->authorization_code = $authorization_code;
        return $this;
    }

    public function getAuthorizationCode() {
        $code = null;

        if (isset($this->authorization_code)) {
            $code = $this->authorization_code;

        } else if (isset($_REQUEST["code"])) {
            $this->authorization_code = $_REQUEST["code"];
            $code = $this->authorization_code;

        } else if (isset($_REQUEST["error"])) {
            throw new ViadeoOAuth2Exception($_REQUEST["error"]);
        }

        return $code;
    }

    // -- redirect uri --------------------------------------------------------
    public function setRedirectURI($redirect_uri) {
        $this->redirect_uri = $redirect_uri;
        return $this;
    }

    public function getRedirectURI() {
        if (isset($this->redirect_uri)) {
            return $this->redirect_uri;
        } else {
            return ViadeoHelper::getCurrentURL();
        }
    }

    // -- OAuth2.0 step 1 -- get authorization code ---------------------------
    public function getAuthorizationURL($extras = array()) {
        $params = array_merge(array(
                'response_type'   =>    'code',
                'client_id'       =>    self::getConfigKey('client_id', true),
                'redirect_uri'    =>    self::getRedirectURI()
              ), $extras);
        $url = self::$authorize_url . "?" . http_build_query($params, null, '&');        
        return $url;
    }

    public function getAuthorizationURLPopup($extras = array()) {
        $extras['display'] = 'popup';
        return $this->getAuthorizationURL($extras);
    }

    public function authorize($extras = array()) {
        header("Location: " . self::getAuthorizationURL($extras));
    }

    // -- OAuth2.0 step 2 -- exchange code with access_token ------------------
    public function setAccessTokenFromCode($extras = array()) {
        $curl_opts = $this->curl_opts;
        $params = array_merge(array(
                'grant_type'     => 'authorization_code',
                'client_id'      => $this->getConfigKey('client_id', true),
                'client_secret'  => $this->getConfigKey('client_secret', true),
                'redirect_uri'   => $this->getRedirectURI(),
                'code'           => $this->getAuthorizationCode()), $extras);

        $curl_opts[CURLOPT_URL] = self::$token_url;
        $curl_opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');

        $ch = curl_init(self::$token_url);
        curl_setopt_array($ch, $curl_opts);

		// mod:btw:dont yell at me
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new ViadeoConnectionException(curl_error($ch));
        }

        list($headers, $body) = explode("\r\n\r\n", $result);
        $result = json_decode($body);

        $ex = null;
        try {
            if (isset($result->error)) {
                throw new ViadeoOAuth2Exception($result->error);
            } else if (isset($result->access_token)) {
                $this->setAccessToken($result->access_token);
            } else {
                throw new ViadeoOAuth2Exception("No token returned !");
            }
        } catch (ViadeoException $e) {
            $ex = $e;
        }
        curl_close($ch);

        if ($ex) {
            throw $ex;
        }

        return $this;
    }

    // -- OAuth2.0 - Automation -----------------------------------------------
    public function OAuth_auto() {
        if ($this->isAuthenticated()) {
            return;
        } else if ($this->getAuthorizationCode() != null) {
            $this->setAccessTokenFromCode();
        } else {
            $this->authorize();
        }
    }

    // == Request management ==================================================
    // ========================================================================

    public function id($id) {
        $d = preg_replace('/^\/*(.*)\/*?$/', '$1', $id);
        return new ViadeoRequest($this, '/' . $id);
    }

    public function get($path) {
        return new ViadeoRequest($this, $path);
    }

    public function post($path) {
        return new ViadeoRequest($this, $path, "POST");
    }

    public function put($path) {
        return new ViadeoRequest($this, $path, "PUT");
    }

    public function del($path) {
        return new ViadeoRequest($this, $path, "DELETE");
    }

    // ------------------------------------------------------------------------

    public function execute(ViadeoRequest $request) {
        if (!$this->isAuthenticated()) {
            throw new ViadeoAuthenticationException("No access token is defined");
        }

        $curl_opts = $this->curl_opts;

        $curl_opts[CURLOPT_HTTPHEADER] = array('Authorization: Bearer ' . $this->getAccessToken());

        $headers = array('Authorization: Bearer ' . $this->getAccessToken());
        if ($request->getMethod() != "GET") {
            # post method dynamically overriden by Tianji adaptation scripts
            $post_method = "application/x-www-form-urlencoded; charset=UTF-8";
            $headers[] = 'Content-Type: '.$post_method;
            $json = (strpos($post_method, 'json') == FALSE) ? false : true;
            $curl_opts[CURLOPT_POSTFIELDS] = $request->getParams(array(), $json);
            $curl_opts[CURLOPT_CUSTOMREQUEST] = $request->getMethod();

            $url = $request->getPath();
        } else {
            $url = $request->getFullPath();
        }
        $curl_opts[CURLOPT_HTTPHEADER] = $headers;
        $curl_opts[CURLOPT_URL] = $url;

        $ch = curl_init($url);
        curl_setopt_array($ch, $curl_opts);

		// mod:btw:dont yell at me
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new ViadeoConnectionException(curl_error($ch));
        }

        list($headers, $body) = explode("\r\n\r\n", $result);
        $result = json_decode($body);

        $ex = null;
        if (isset($result->error)) {
            curl_close($ch);
            throw new ViadeoAPIException($result->error->type . " - " . $result->error->message[0]);
        }
        curl_close($ch);

        return isset($result->id) ? new ViadeoGraphObject($this, $result) : $result;
    }

    public function object($data) {
        return new ViadeoGraphObject($this, $data);
    }
}

class ViadeoHelper {
    // == Helper tools ========================================================
    // ========================================================================

    // Retrieve the current page URL ------------------------------------------
    public static function getCurrentURL() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on")) {$pageURL .= "s";}
        $pageURL .= "://";

        $pageURL .= $_SERVER["SERVER_NAME"];
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= ":".$_SERVER["SERVER_PORT"];
        }
        $pageURL .= $_SERVER["SCRIPT_NAME"];

        return $pageURL;
    }
}
