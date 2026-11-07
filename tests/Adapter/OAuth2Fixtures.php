<?php

namespace HybridauthTest\Adapter;

class OAuth2Http extends \Hybridauth\HttpClient\Curl
{
    public $requests = [];
    public $body = '{"access_token":"synthetic-new","expires_in":3600}';
    public $headers = [];
    public $status = 200;
    public $fail = false;

    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        $this->requests[] = [$uri, $method, $parameters, $headers];
        if ($this->fail) { throw new \RuntimeException('Synthetic exchange failure'); }
        return $this->body;
    }
    public function getResponseHeader() { return $this->headers; }
    public function getResponseHttpCode() { return $this->status; }
}

class OAuth2Adapter extends \Hybridauth\Adapter\OAuth2
{
    protected $authorizeUrl = 'https://provider.invalid/authorize';
    protected $accessTokenUrl = 'https://provider.invalid/token';
    protected $apiBaseUrl = 'https://provider.invalid/api/';
    public $input = [];

    public function url($parameters = []) { return $this->getAuthorizeUrl($parameters); }
    public function accept($response) { return $this->validateAccessTokenExchange($response); }
    public function acceptRefresh($response) { return $this->validateRefreshAccessToken($response); }
    public function exchange($code) { return $this->exchangeCodeForAccessToken($code); }
    public function finish() { return $this->authenticateFinish(); }
    public function checkError() { return $this->authenticateCheckError(); }
    public function filterInput($type, $name, $filter = FILTER_DEFAULT, $options = 0) {
        return isset($this->input[$name]) ? $this->input[$name] : null;
    }
}

class FormTokenAdapter extends OAuth2Adapter
{
    protected $tokenResponseFormat = 'form';
}
