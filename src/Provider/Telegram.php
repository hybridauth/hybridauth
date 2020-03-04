<?php

namespace Hybridauth\Provider;

use Hybridauth\HttpClient\Util;
use Hybridauth\Data\Collection;
use Hybridauth\User\Profile;
use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\UnexpectedApiResponseException;

/**
 * Telegram provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'      => ['id' => 'your_bot_name', 'secret' => 'your_bot_token'],
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Telegram($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   }
 *   catch(\Exception $e) {
 *       print $e->getMessage();
 *   }
 */
class Telegram extends AbstractAdapter implements AdapterInterface
{

    protected $botId = '';

    protected $botSecret = '';

    protected $callbackUrl = '';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->botId = $this->config->filter('keys')->get('id');
        $this->botSecret = $this->config->filter('keys')->get('secret');
        $this->callbackUrl = $this->config->get('callback');

        if (!$this->botId || !$this->botSecret) {
            throw new InvalidApplicationCredentialsException(
                'Your application id is required in order to connect to ' . $this->providerId
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));
        if (!filter_input(INPUT_GET, 'hash')) {
            $this->authenticateBegin();
        } else {
            $this->authenticateCheckError();
            $this->authenticateFinish();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $data = new Collection($this->parseAuthData());

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier    = $data->get('id');
        $userProfile->firstName     = $data->get('first_name');
        $userProfile->lastName      = $data->get('last_name');
        $userProfile->displayName   = $data->get('username');
        $userProfile->photoURL      = $data->get('photo_url');

        return $userProfile;
    }

    /**
     * See: https://telegram.im/widget-login.php
     * See: https://gist.github.com/anonymous/6516521b1fb3b464534fbc30ea3573c2
     */
    protected function authenticateCheckError()
    {
        $auth_data = $this->parseAuthData();

        $check_hash = $auth_data['hash'];
        unset($auth_data['hash']);
        $data_check_arr = [];

        foreach ($auth_data as $key => $value) {
            if (!empty($value)) {
                $data_check_arr[] = $key . '=' . $value;
            }
        }
        sort($data_check_arr);

        $data_check_string = implode("\n", $data_check_arr);
        $secret_key = hash('sha256', $this->botSecret, true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        if (strcmp($hash, $check_hash) !== 0) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is NOT from Telegram')
            );
        }

        if ((time() - $auth_data['auth_date']) > 86400) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is outdated')
            );
        }
    }

    /**
     * See: https://telegram.im/widget-login.php
     */
    protected function authenticateBegin()
    {
        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)));

        exit(
            <<<HTML
<center>
    <script async src="https://telegram.org/js/telegram-widget.js?7"
            data-telegram-login="{$this->botId}"
            data-size="large"
            data-auth-url="{$this->callbackUrl}"
            data-request-access="write">
    </script>
</center>
HTML
        );
    }

    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [Util::getCurrentUrl(true)]
        );
        $this->initialize();
    }

    protected function parseAuthData()
    {
        return [
            'id'            => filter_input(INPUT_GET, 'id'),
            'first_name'    => filter_input(INPUT_GET, 'first_name'),
            'last_name'     => filter_input(INPUT_GET, 'last_name'),
            'username'      => filter_input(INPUT_GET, 'username'),
            'photo_url'     => filter_input(INPUT_GET, 'photo_url'),
            'auth_date'     => filter_input(INPUT_GET, 'auth_date'),
            'hash'          => filter_input(INPUT_GET, 'hash'),
        ];
    }
}
