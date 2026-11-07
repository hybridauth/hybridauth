<?php

// Runs in a child process with open_basedir set; prints which OpenID transport was selected.
require dirname(__DIR__, 3) . '/vendor/autoload.php';

class TransportProbe extends \Hybridauth\Thirdparty\OpenID\LightOpenID
{
    public function __construct() { }
    public function probe() { return $this->request('http://8.8.8.8/'); }
    protected function request_curl($url, $method = 'GET', $params = [], $update = false) { $this->responseCode = 200; return 'curl'; }
    protected function request_streams($url, $method = 'GET', $params = [], $update = false) { $this->responseCode = 200; return 'streams'; }
}

echo ini_get('open_basedir') !== '' ? (new TransportProbe())->probe() : 'open_basedir not applied';
