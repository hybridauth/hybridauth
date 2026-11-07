<?php

namespace Hybridauth\Provider;

use Hybridauth\HttpClient\Util;
use Hybridauth\Data\Collection;
use Hybridauth\Data\Random;
use Hybridauth\User\Profile;
use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\UnexpectedApiResponseException;

/**
 * Telegram Login Widget provider adapter (legacy bot-token flow).
 *
 * Use this adapter for bots configured with /setdomain. For Telegram's OpenID Connect
 * login (Client ID and Client Secret from Bot Settings → Web Login) use the Telegram provider.
 *
 * To set up the widget you need to interactively create a bot using the
 * Telegram mobile app, talking to botfather. The minimum conversation
 * will look like:
 *
 * /newbot
 * My Bot Title
 * nameofmynewbot
 * /setdomain
 * @nameofmynewbot
 * mydomain.com
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => 'your_bot_name', 'secret' => 'your_bot_token'],
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\TelegramWidget($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       print $e->getMessage();
 *   }
 */
class TelegramWidget extends AbstractAdapter implements AdapterInterface
{
    protected $botId = '';

    protected $botSecret = '';

    protected $callbackUrl = '';

    /**
     * IPD API Documentation
     *
     * OPTIONAL.
     *
     * @var string
     */
    protected $apiDocumentation = 'https://core.telegram.org/bots';

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
        if (!$this->filterInput(INPUT_GET, 'hash')) {
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
    public function isConnected()
    {
        $authData = $this->getStoredData('auth_data');
        return !empty($authData);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $data = new Collection($this->getStoredData('auth_data'));

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier = $data->get('id');
        $userProfile->firstName = $data->get('first_name');
        $userProfile->lastName = $data->get('last_name');
        $userProfile->displayName = $data->get('username');
        $userProfile->photoURL = $data->get('photo_url');
        $username = $data->get('username');
        if (!empty($username)) {
            // Only some accounts have usernames.
            $userProfile->profileURL = "https://t.me/{$username}";
        }

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

        if (!Random::equals($hash, $check_hash)) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is NOT from Telegram')
            );
        }

        if (!is_string($auth_data['auth_date']) || !ctype_digit($auth_data['auth_date'])
            || (time() - $auth_data['auth_date']) > 86400 || $auth_data['auth_date'] > time() + 120
        ) {
            throw new InvalidAuthorizationCodeException(
                sprintf('Provider returned an error: %s', 'Data is outdated')
            );
        }

        $expectedState = $this->getStoredData('authorization_state');
        $this->deleteStoredData('authorization_state');
        if (!Random::equals($expectedState, $this->filterInput(INPUT_GET, 'ha_state'))) {
            throw new InvalidAuthorizationCodeException('Invalid or consumed Telegram login state.');
        }
    }

    /**
     * See: https://telegram.im/widget-login.php
     */
    protected function authenticateBegin()
    {
        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)));

        exit($this->getLoginHtml());
    }

    /** Render a widget whose callback is bound to this session and login attempt. */
    protected function getLoginHtml()
    {
        $state = Random::token();
        $this->storeData('authorization_state', $state);
        $callback = explode('#', $this->callbackUrl, 2);
        $callbackUrl = $callback[0] . (strpos($callback[0], '?') === false ? '?' : '&') . 'ha_state=' . $state;
        if (isset($callback[1])) {
            $callbackUrl .= '#' . $callback[1];
        }
        $callbackUrl = htmlspecialchars($callbackUrl, ENT_QUOTES, 'UTF-8');
        $botId = htmlspecialchars($this->botId, ENT_QUOTES, 'UTF-8');
        $nonce = htmlspecialchars((string) $this->config->get('nonce'), ENT_QUOTES, 'UTF-8');
        $nonce_code = $nonce === '' ? '' : "nonce=\"{$nonce}\"";

        return <<<HTML
<center>
    <script async src="https://telegram.org/js/telegram-widget.js?7"
            {$nonce_code}
            data-telegram-login="{$botId}"
            data-size="large"
            data-auth-url="{$callbackUrl}"
            data-request-access="write">
    </script>
</center>
HTML;
    }

    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [Util::getCurrentUrl(true)]
        );

        $this->storeData('auth_data', $this->parseAuthData());

        $this->initialize();
    }

    protected function parseAuthData()
    {
        return [
            'id' => $this->filterInput(INPUT_GET, 'id'),
            'first_name' => $this->filterInput(INPUT_GET, 'first_name'),
            'last_name' => $this->filterInput(INPUT_GET, 'last_name'),
            'username' => $this->filterInput(INPUT_GET, 'username'),
            'photo_url' => $this->filterInput(INPUT_GET, 'photo_url'),
            'auth_date' => $this->filterInput(INPUT_GET, 'auth_date'),
            'hash' => $this->filterInput(INPUT_GET, 'hash'),
        ];
    }
}
