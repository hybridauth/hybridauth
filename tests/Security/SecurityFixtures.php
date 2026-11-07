<?php

namespace HybridauthTest\Security;

use Hybridauth\Data\Random;
use Hybridauth\HttpClient\Curl;
use Hybridauth\HttpClient\Url;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Thirdparty\OpenID\LightOpenID;

class MemoryStorage implements StorageInterface
{
    public $items = [];
    public function get($key) { return isset($this->items[$key]) ? $this->items[$key] : null; }
    public function set($key, $value) { $this->items[$key] = $value; }
    public function delete($key) { unset($this->items[$key]); }
    public function deleteMatch($key) {
        foreach (array_keys($this->items) as $item) {
            if (strpos($item, $key) !== false) { unset($this->items[$item]); }
        }
    }
    public function clear() { $this->items = []; }
}

class TelegramWidget extends \Hybridauth\Provider\TelegramWidget
{
    public $input = [];
    public function widget() { return $this->getLoginHtml(); }
    public function filterInput($type, $name, $filter = FILTER_DEFAULT, $options = 0) {
        return isset($this->input[$name]) ? $this->input[$name] : null;
    }
}

class Google extends \Hybridauth\Provider\Google
{
    public $input = [];
    public $exchanges = 0;
    public function url() { return $this->getAuthorizeUrl(); }
    public function finish() { return $this->authenticateFinish(); }
    public function filterInput($type, $name, $filter = FILTER_DEFAULT, $options = 0) {
        return isset($this->input[$name]) ? $this->input[$name] : null;
    }
    protected function exchangeCodeForAccessToken($code) {
        $this->exchanges++;
        return '{"access_token":"demo-token"}';
    }
}

class Twitter extends \Hybridauth\Provider\Twitter
{
    public $flow;
    protected function authenticateBegin() { $this->flow = 'begin'; }
    protected function authenticateFinish() { $this->flow = 'finish'; }
}

class OpenIDAdapter extends \Hybridauth\Provider\OpenID
{
    public $flow;
    protected function authenticateBegin() { $this->flow = 'begin'; }
    protected function authenticateFinish() { $this->flow = 'finish'; }
}

class Apple extends \Hybridauth\Provider\Apple
{
    protected function configure() { $this->config->set('verifyTokenSignature', false); }
    protected function initialize() { }
    public function seedToken($token) { $this->storeData('id_token', $token); }
}

class X extends \Hybridauth\Provider\X
{
    public function checkError() { $this->authenticateCheckError(); }
}
