<?php

/*!
 * HybridAuth
 * https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
 *  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

namespace Hybridauth\Provider;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Data;
use Hybridauth\Exception\AuthorizationDeniedException;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\HttpClient;
use Hybridauth\User;

/**
 * Telegram Login via OpenID Connect (Authorization Code + PKCE).
 *
 * Setup:
 *   1. Create a bot via @BotFather.
 *   2. Go to Bot Settings → Web Login, register your callback URL.
 *   3. Copy the Client ID and Client Secret shown there.
 *      These are NOT the same as the bot token.
 *
 * Configuration:
 *
 *   $config = [
 *       'callback' => 'https://example.com/auth/callback',
 *       'keys'     => [
 *           'id'     => 'CLIENT_ID',
 *           'secret' => 'CLIENT_SECRET',
 *       ],
 *       'scope' => 'openid profile phone', // optional, default: 'openid profile'
 *   ];
 *
 * Available scopes: openid (required), profile, phone, telegram:bot_access
 *
 * Profile fields: identifier, displayName, firstName, lastName,
 *                 photoURL, profileURL, phone (requires 'phone' scope)
 *
 * Restoring tokens from database:
 *   $adapter->setAccessToken($tokensFromDb);
 *   $profile = $adapter->getUserProfile();
 *
 * @see https://core.telegram.org/bots/telegram-login
 */
class Telegram extends AbstractAdapter
{
    const AUTH_URL   = 'https://oauth.telegram.org/auth';
    const TOKEN_URL  = 'https://oauth.telegram.org/token';
    const JWKS_URL   = 'https://oauth.telegram.org/.well-known/jwks.json';
    const ISSUER     = 'https://oauth.telegram.org';
    const CLOCK_SKEW = 30;

    /** @var string */
    protected $clientId = '';

    /** @var string */
    protected $clientSecret = '';

    /** @var string */
    protected $scope = 'openid profile';

    /** @var string */
    protected $apiDocumentation = 'https://core.telegram.org/bots/telegram-login';

    protected function configure()
    {
        $this->clientId     = $this->config->filter('keys')->get('id');
        $this->clientSecret = $this->config->filter('keys')->get('secret');

        if (! $this->clientId || ! $this->clientSecret) {
            throw new InvalidApplicationCredentialsException(
                'Your application id is required in order to connect to ' . $this->providerId
            );
        }

        if ($this->config->exists('scope')) {
            $this->scope = trim($this->config->get('scope'));
        }

        if (strpos($this->scope, 'openid') === false) {
            $this->scope = 'openid ' . $this->scope;
        }

        $this->setCallback($this->config->get('callback'));

        if ($this->config->exists('tokens')) {
            $this->setAccessToken($this->config->get('tokens'));
        }
    }

    protected function initialize()
    {
    }

    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isConnected()) {
            return true;
        }

        if ($this->getStoredData('id_token')) {
            $this->logger->debug(sprintf(
                '%s::authenticate() — id_token expired at %s, clearing session.',
                get_class($this),
                date('Y-m-d H:i:s', (int) $this->getStoredData('expires_at'))
            ));
            $this->clearStoredData();
        }

        try {
            $this->authenticateCheckError();

            $code = $this->filterInput(INPUT_GET, 'code');

            if (empty($code)) {
                $this->authenticateBegin();
            } else {
                $this->authenticateFinish($code);
            }
        } catch (\Exception $e) {
            $this->clearStoredData();
            throw $e;
        }

        return null;
    }

    /**
     * @throws AuthorizationDeniedException
     * @throws InvalidAuthorizationCodeException
     */
    protected function authenticateCheckError()
    {
        $error = $this->filterInput(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($error)) {
            return;
        }

        $description = $this->filterInput(INPUT_GET, 'error_description', FILTER_SANITIZE_SPECIAL_CHARS);
        $uri         = $this->filterInput(INPUT_GET, 'error_uri',         FILTER_SANITIZE_SPECIAL_CHARS);

        $message = sprintf(
            'Telegram returned an error: %s %s %s',
            $error,
            isset($description) ? $description : '',
            isset($uri)         ? $uri         : ''
        );

        if ($error === 'access_denied') {
            throw new AuthorizationDeniedException($message);
        }

        throw new InvalidAuthorizationCodeException($message);
    }

    /**
     * Generate PKCE verifier/challenge, state and nonce, store them,
     * then redirect the browser to Telegram's authorization endpoint.
     * Execution does not return — exits after the Location header.
     */
    protected function authenticateBegin()
    {
        $codeVerifier  = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);
        $state         = bin2hex($this->secureRandomBytes(16));
        $nonce         = bin2hex($this->secureRandomBytes(16));

        $this->storeData('pkce_code_verifier',  $codeVerifier);
        $this->storeData('authorization_state', $state);
        $this->storeData('oidc_nonce',          $nonce);

        $params = array(
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->callback,
            'response_type'         => 'code',
            'scope'                 => $this->scope,
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        );

        $authUrl = self::AUTH_URL . '?' . http_build_query($params, '', '&');

        $this->logger->debug(
            sprintf('%s::authenticateBegin(), redirecting to:', get_class($this)),
            array($authUrl)
        );

        HttpClient\Util::redirect($authUrl);
    }

    /**
     * @param  string $code
     *
     * @throws InvalidAuthorizationStateException
     * @throws UnexpectedApiResponseException
     */
    protected function authenticateFinish($code)
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            array(HttpClient\Util::getCurrentUrl(true))
        );

        $returnedState = $this->filterInput(INPUT_GET, 'state');
        $savedState    = $this->getStoredData('authorization_state');

        if (! $savedState || $savedState !== $returnedState) {
            $this->deleteStoredData('authorization_state');
            throw new InvalidAuthorizationStateException(
                'The authorization state [state=' . htmlentities((string) $returnedState, ENT_QUOTES) . '] '
                . 'is either invalid or has already been consumed.'
            );
        }

        $codeVerifier = (string) $this->getStoredData('pkce_code_verifier');
        $nonce        = (string) $this->getStoredData('oidc_nonce');

        $rawResponse = $this->httpClient->request(
            self::TOKEN_URL,
            'POST',
            array(
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $this->callback,
                'client_id'     => $this->clientId,
                'code_verifier' => $codeVerifier,
            ),
            array(
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            )
        );

        $this->validateApiResponse('Telegram token endpoint request failed');

        $tokenData = new Data\Collection((array) json_decode($rawResponse, true));

        if ($tokenData->exists('error')) {
            throw new UnexpectedApiResponseException(sprintf(
                'Telegram token endpoint error: %s — %s',
                $tokenData->get('error'),
                $tokenData->get('error_description') !== null ? $tokenData->get('error_description') : '(no description)'
            ));
        }

        if (! $tokenData->exists('id_token')) {
            throw new UnexpectedApiResponseException(
                'Telegram token endpoint returned no "id_token". Raw response: ' . $rawResponse
            );
        }

        $idToken = $tokenData->get('id_token');
        $claims  = $this->validateIdToken($idToken, $nonce !== '' ? $nonce : null);

        $this->storeData('access_token', $tokenData->get('access_token'));
        $this->storeData('token_type',   $tokenData->get('token_type'));
        $this->storeData('id_token',     $idToken);

        if ($tokenData->exists('expires_in')) {
            $this->storeData('expires_in', $tokenData->get('expires_in'));
            $this->storeData('expires_at', time() + (int) $tokenData->get('expires_in'));
        }

        $this->storeData('user_claims', json_encode($claims));

        $this->deleteStoredData('pkce_code_verifier');
        $this->deleteStoredData('authorization_state');
        $this->deleteStoredData('oidc_nonce');

        $this->initialize();
    }

    public function isConnected()
    {
        if (! $this->getStoredData('id_token')) {
            return false;
        }

        $expiresAt = $this->getStoredData('expires_at');

        if ($expiresAt === null) {
            return true;
        }

        return (int) $expiresAt > time();
    }

    public function getAccessToken()
    {
        $tokens  = parent::getAccessToken();
        $idToken = $this->getStoredData('id_token');

        if ($idToken) {
            $tokens['id_token'] = $idToken;
        }

        return $tokens;
    }

    /**
     *
     * Accepts expired id_tokens (e.g. restored from database).
     * Verifies signature, iss and aud but skips exp check.
     *
     * @param array $tokens  Must contain 'id_token'.
     */
    public function setAccessToken($tokens = array())
    {
        if (empty($tokens['id_token'])) {
            throw new UnexpectedApiResponseException(
                'setAccessToken() requires "id_token" to be present in the supplied array.'
            );
        }

        $claims = $this->parseIdTokenPayload($tokens['id_token']);

        $this->clearStoredData();

        foreach ($tokens as $key => $value) {
            $this->storeData($key, $value);
        }

        $this->storeData('user_claims', json_encode($claims));

        $this->initialize();
    }

    public function getUserProfile()
    {
        $claims = $this->getClaimsFromStorage();

        $profile             = new User\Profile();
        $profile->identifier = $claims['sub'];

        $fullName = trim(isset($claims['name']) ? $claims['name'] : '');

        $profile->displayName = $fullName
            ? $fullName
            : (isset($claims['preferred_username']) ? $claims['preferred_username'] : null);
        $profile->firstName = $this->parseFirstName($fullName);
        $profile->lastName  = $this->parseLastName($fullName);
        $profile->photoURL  = isset($claims['picture'])      ? $claims['picture']      : null;
        $profile->phone     = isset($claims['phone_number']) ? $claims['phone_number'] : null;

        if (! empty($claims['preferred_username'])) {
            $profile->profileURL = 'https://t.me/' . ltrim($claims['preferred_username'], '@');
        }

        return $profile;
    }

    /**
     * Return all JWT claims as decoded from Telegram's id_token.
     *
     * @return array
     *
     * @throws UnexpectedApiResponseException
     */
    public function getRawClaims()
    {
        return $this->getClaimsFromStorage();
    }

    /**
     * @return array
     *
     * @throws UnexpectedApiResponseException
     */
    protected function getClaimsFromStorage()
    {
        $claimsJson = $this->getStoredData('user_claims');

        if ($claimsJson) {
            $claims = json_decode($claimsJson, true);

            if (is_array($claims) && ! empty($claims['sub'])) {
                return $claims;
            }
        }

        $idToken = $this->getStoredData('id_token');

        if (! $idToken) {
            throw new UnexpectedApiResponseException(
                'Provider API returned an unexpected response.'
            );
        }

        $claims = $this->parseIdTokenPayload($idToken);

        $this->storeData('user_claims', json_encode($claims));

        return $claims;
    }

    /**
     * Full OIDC validation: signature + iss + aud + exp + nonce.
     * Used only during authenticateFinish().
     *
     * @param  string      $idToken
     * @param  string|null $expectedNonce
     * @return array
     *
     * @throws UnexpectedApiResponseException
     */
    protected function validateIdToken($idToken, $expectedNonce)
    {
        $payload = $this->parseIdTokenPayload($idToken);

        $exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;

        if ($exp < (time() - self::CLOCK_SKEW)) {
            throw new UnexpectedApiResponseException(
                'JWT id_token has expired (exp=' . $exp . ').'
            );
        }

        $nonce = isset($payload['nonce']) ? $payload['nonce'] : '';

        if ($expectedNonce !== null && $nonce !== $expectedNonce) {
            throw new UnexpectedApiResponseException(
                'JWT nonce mismatch — possible replay attack.'
            );
        }

        return $payload;
    }

    /**
     * Decode and verify JWT signature + iss + aud. Does not check exp or nonce.
     * Used for setAccessToken() and storage fallback (tokens may be expired).
     *
     * @param  string $idToken
     * @return array
     *
     * @throws UnexpectedApiResponseException
     */
    protected function parseIdTokenPayload($idToken)
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new UnexpectedApiResponseException(
                'Malformed JWT id_token: expected 3 dot-separated segments.'
            );
        }

        $headerB64    = $parts[0];
        $payloadB64   = $parts[1];
        $signatureB64 = $parts[2];

        $header  = json_decode($this->base64UrlDecode($headerB64),  true);
        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (! is_array($header) || ! is_array($payload)) {
            throw new UnexpectedApiResponseException(
                'Failed to JSON-decode the JWT header or payload.'
            );
        }

        $iss = isset($payload['iss']) ? $payload['iss'] : '';

        if ($iss !== self::ISSUER) {
            throw new UnexpectedApiResponseException(sprintf(
                'JWT issuer (iss) mismatch: expected "%s", got "%s".',
                self::ISSUER,
                $iss !== '' ? $iss : '(missing)'
            ));
        }

        $aud = isset($payload['aud']) ? (string) $payload['aud'] : '';

        if ($aud !== (string) $this->clientId) {
            throw new UnexpectedApiResponseException(sprintf(
                'JWT audience (aud) mismatch: expected "%s", got "%s".',
                $this->clientId,
                $aud !== '' ? $aud : '(missing)'
            ));
        }

        $this->verifyJwtSignature(
            $headerB64,
            $payloadB64,
            $this->base64UrlDecode($signatureB64),
            $header
        );

        return $payload;
    }

    /**
     * @param  string $headerB64
     * @param  string $payloadB64
     * @param  string $signature
     * @param  array  $header
     *
     * @throws UnexpectedApiResponseException
     */
    protected function verifyJwtSignature($headerB64, $payloadB64, $signature, array $header)
    {
        $alg = strtoupper(isset($header['alg']) ? $header['alg'] : '');
        $kid = isset($header['kid']) ? $header['kid'] : null;

        $algoMap = array(
            'RS256' => OPENSSL_ALGO_SHA256,
            'RS384' => OPENSSL_ALGO_SHA384,
            'RS512' => OPENSSL_ALGO_SHA512,
        );

        if (! isset($algoMap[$alg])) {
            throw new UnexpectedApiResponseException(
                'Unsupported or missing JWT signing algorithm: "' . $alg . '".'
            );
        }

        $publicKey = $this->jwkToRsaPublicKey($this->findJwk($kid));

        $result = openssl_verify(
            $headerB64 . '.' . $payloadB64,
            $signature,
            $publicKey,
            $algoMap[$alg]
        );

        if ($result !== 1) {
            throw new UnexpectedApiResponseException(
                'JWT signature verification failed. OpenSSL: ' . openssl_error_string()
            );
        }
    }

    /**
     * Fetch and cache Telegram's JWKS, return the key matching $kid.
     *
     * @param  string|null $kid
     * @return array
     *
     * @throws UnexpectedApiResponseException
     */
    protected function findJwk($kid)
    {
        static $cache = null;

        if ($cache === null) {
            $raw   = $this->httpClient->request(self::JWKS_URL);
            $this->validateApiResponse('Failed to fetch JWKS from Telegram');
            $cache = json_decode($raw, true);

            if (empty($cache['keys']) || ! is_array($cache['keys'])) {
                throw new UnexpectedApiResponseException(
                    'Failed to parse JWKS from: ' . self::JWKS_URL
                );
            }
        }

        foreach ($cache['keys'] as $jwk) {
            $jwkKid = isset($jwk['kid']) ? $jwk['kid'] : null;
            if ($kid === null || $jwkKid === $kid) {
                return $jwk;
            }
        }

        throw new UnexpectedApiResponseException(sprintf(
            'No JWKS key found for kid="%s". Telegram may have rotated the signing key.',
            $kid !== null ? $kid : '(none)'
        ));
    }

    /**
     * Convert an RSA JWK to an OpenSSL public key resource.
     *
     * @param  array $jwk
     * @return resource|\OpenSSLAsymmetricKey
     *
     * @throws UnexpectedApiResponseException
     */
    protected function jwkToRsaPublicKey(array $jwk)
    {
        if ((isset($jwk['kty']) ? $jwk['kty'] : '') !== 'RSA') {
            throw new UnexpectedApiResponseException(
                'Only RSA JWKs are supported. Got: "' . (isset($jwk['kty']) ? $jwk['kty'] : 'missing') . '".'
            );
        }

        if (empty($jwk['n']) || empty($jwk['e'])) {
            throw new UnexpectedApiResponseException('JWK is missing RSA parameters "n" or "e".');
        }

        $n = $this->base64UrlDecode($jwk['n']);
        $e = $this->base64UrlDecode($jwk['e']);

        $rsaBody   = $this->asn1Seq($this->asn1Int($n) . $this->asn1Int($e));
        $oid       = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $bitString = "\x03" . $this->asn1Len(strlen($rsaBody) + 1) . "\x00" . $rsaBody;
        $spki      = "\x30" . $this->asn1Len(strlen($oid . $bitString)) . $oid . $bitString;

        $pem = "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($spki), 64, "\n")
             . "-----END PUBLIC KEY-----\n";

        $key = openssl_pkey_get_public($pem);

        if ($key === false) {
            throw new UnexpectedApiResponseException(
                'openssl_pkey_get_public() failed. OpenSSL: ' . openssl_error_string()
            );
        }

        return $key;
    }

    /**
     * @return string
     */
    protected function generateCodeVerifier()
    {
        return rtrim(strtr(base64_encode($this->secureRandomBytes(32)), '+/', '-_'), '=');
    }

    /**
     * @param  string $verifier
     * @return string
     */
    protected function generateCodeChallenge($verifier)
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Secure random bytes compatible with PHP 5.6 and PHP 7+.
     *
     * @param  int    $length
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function secureRandomBytes($length)
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }

        $strong = false;
        $bytes  = openssl_random_pseudo_bytes($length, $strong);

        if ($bytes === false || ! $strong) {
            throw new \RuntimeException(
                'Unable to generate cryptographically secure random bytes. '
                . 'Ensure the OpenSSL PHP extension is enabled.'
            );
        }

        return $bytes;
    }

    /**
     * @param  string $input
     * @return string
     */
    protected function base64UrlDecode($input)
    {
        $padded = $input . str_repeat('=', (4 - strlen($input) % 4) % 4);
        return base64_decode(strtr($padded, '-_', '+/'));
    }

    /**
     * @param  string $bytes
     * @return string
     */
    protected function asn1Int($bytes)
    {
        if (ord($bytes[0]) > 0x7f) {
            $bytes = "\x00" . $bytes;
        }
        return "\x02" . $this->asn1Len(strlen($bytes)) . $bytes;
    }

    /**
     * @param  string $contents
     * @return string
     */
    protected function asn1Seq($contents)
    {
        return "\x30" . $this->asn1Len(strlen($contents)) . $contents;
    }

    /**
     * @param  int $length
     * @return string
     */
    protected function asn1Len($length)
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $packed = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($packed)) . $packed;
    }

    /**
     * @param  string $fullName
     * @return string
     */
    protected function parseFirstName($fullName)
    {
        $parts = explode(' ', trim($fullName), 2);
        return isset($parts[0]) ? $parts[0] : '';
    }

    /**
     * @param  string $fullName
     * @return string
     */
    protected function parseLastName($fullName)
    {
        $parts = explode(' ', trim($fullName), 2);
        return isset($parts[1]) ? $parts[1] : '';
    }
}
