<?php
# om is oauth-mini - a simple implementation of a useful subset of OAuth.
# It's designed to be useful and reusable but not general purpose.
#
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

# Limitations:
#  - doesn't support multiple values for keys

# UTF-8 and escape the key/value pairs
function __om_escape($s) {
    return str_replace('%7E', '~', rawurlencode($s));
};

/**
 * Generates an OAuth signature
 *
 * @param array  $consumer  an array with the consumer key and consumer secret
 * @param string $url       the URL being requested
 * @param array  $params    the POST parameters
 * @param array  $token     the OAuth token, an array of token and token secret
 * @param string $method    the HTTP method, POST by default
 * @param string $realm     the HTTP authorization realm (optional)
 * @param string $timestamp the OAuth timestamp (optional, will be automatically generated)
 * @param string $nonce     the OAuth nonce (optional, will be automatically generated)
 * @return An Authorization header
 *
 */
function om($consumer, $url, $params, $token=NULL, $method='POST', $realm=NULL, $timestamp=NULL, $nonce=NULL) {
    # the method must be upper-case
    $method = strtoupper($method);

    # normalize the URL
    $parts = parse_url($url);
    # the scheme and the host are lower-cased
    $normalized_url = strtolower($parts['scheme']) . '://' .
        strtolower($parts['host']);
    # include non-default port numbers
    if (array_key_exists('port', $parts) && (
            (strtolower($parts['scheme']) == 'http' && $parts['port'] != 80) ||
            (strtolower($parts['scheme']) == 'https' && $parts['port'] != 443))) {
        $normalized_url .= ':' . $parts['port'];
    }
    # the path goes in as-is
    $normalized_url .= $parts['path'];

    # add query-string params (if any) to the params list since they must be
    # included in the signature
    if (array_key_exists('query', $parts)) {
        parse_str($parts['query'], $url_params);
        $params = array_merge($params, $url_params);
    }

    # add OAuth params
    $params['oauth_version'] = '1.0';
    if ($timestamp == NULL) {
        $params['oauth_timestamp'] = ''.time();
    } else {
        $params['oauth_timestamp'] = $timestamp;
    }
    if ($nonce == NULL) {
        $params['oauth_nonce'] = ''.rand(0,1000000);
    } else {
        $params['oauth_nonce'] = $nonce;
    }
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['oauth_consumer_key'] = $consumer[0];

    # the consumer secret is the first half of the HMAC-SHA1 key
    $hmac_key = $consumer[1] . '&';

    if ($token != NULL) {
        # include a token in params
        $params['oauth_token'] = $token[0];
        # and the token secret in the HMAC-SHA1 key
        $hmac_key .= $token[1];
    }

    # sort the params by key
    ksort($params, SORT_STRING);

    # escape the params and combine them into a string
    $normalized_params = "";
    foreach ($params as $key=>$value) {
        $normalized_params .= '&' . __om_escape($key) . '=' . __om_escape($value);
    }
    $normalized_params = substr($normalized_params, 1);

    # build the signature base string
    $signature_base_string = __om_escape($method) . '&' .
        __om_escape($normalized_url) . '&' . __om_escape($normalized_params);

    # HMAC-SHA1
    $oauth_signature = base64_encode(hash_hmac("sha1", $signature_base_string,
        $hmac_key, TRUE));

    # Build the Authorization header
    $authorization_params = array();
    if ($realm) {
        array_push($authorization_params, 'realm="' . __om_escape($realm) . '"');
    }
    array_push($authorization_params,
        'oauth_signature="' . $oauth_signature . '"');

    $oauth_parameters = array(
        'oauth_version', 'oauth_timestamp', 'oauth_nonce', 'oauth_signature_method',
        'oauth_signature', 'oauth_consumer_key', 'oauth_token'
    );
    foreach ($params as $key=>$value) {
        if (in_array($key, $oauth_parameters)) {
            array_push($authorization_params,
                __om_escape($key) . '="' . __om_escape($value) . '"');
        }
    }

    return 'OAuth ' . implode(', ', $authorization_params);
}
?>