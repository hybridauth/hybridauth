<?php
# (c) 2011 Rdio Inc
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
# THE SOFTWARE.


require_once 'om.php';

class Rdio {
    function __construct($consumer, $token=NULL) {
        $this->consumer = $consumer;
        $this->token = $token;
    }

    private function __signed_post($url, $params, $isMultipart=FALSE) {
        if ($isMultipart) {
            // Don't include the parameters in the signature if multipart/form-data
            // http://tools.ietf.org/html/rfc5849#section-3.4.1.3.1
            $auth = om($this->consumer, $url, array(), $this->token);
            $postbody = $params;
        } else {
            $auth = om($this->consumer, $url, $params, $this->token);
            $postbody = http_build_query($params);
        }

        $curl = curl_init($url);
        // curl_setopt($curl, CURLOPT_VERBOSE, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postbody);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: '.$auth));
        $body = curl_exec($curl);
        curl_close($curl);
        return $body;
    }

    function begin_authentication($callback_url) {
        $response = $this->__signed_post('http://api.rdio.com/oauth/request_token',
            array('oauth_callback'=>$callback_url));
        $parsed = array();
        parse_str($response, $parsed);
        $this->token = array($parsed['oauth_token'], $parsed['oauth_token_secret']);
        return $parsed['login_url'] . '?oauth_token=' . $parsed['oauth_token'];
    }

    function complete_authentication($verifier) {
        $response = $this->__signed_post('http://api.rdio.com/oauth/access_token',
            array('oauth_verifier'=>$verifier));
        $parsed = array();
        parse_str($response, $parsed);
        $this->token = array($parsed['oauth_token'], $parsed['oauth_token_secret']);
    }

    function call($method, $params=array(), $isMultipart=FALSE) {
        $params['method'] = $method;
        return json_decode($this->__signed_post('http://api.rdio.com/1/', $params,
            $isMultipart));
    }
};
?>