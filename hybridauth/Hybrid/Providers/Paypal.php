<?php

/**
 * @file
 * HybridAuth
 * http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
 * (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
 */

use PayPal\Api\OpenIdSession;
use PayPal\Api\OpenIdTokeninfo;
use PayPal\Api\OpenIdUserinfo;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

/**
 * PayPal OAuth Class.
 *
 * @package  HybridAuth providers package
 * @version  1.0
 * @license  BSD License
 */

/**
 * Hybrid_Providers_Paypal - PayPal provider adapter based on OAuth2 protocol.
 */
class Hybrid_Providers_Paypal extends Hybrid_Provider_Model
{

    /**
     * The access privileges that you are requesting for
     * from the user. Pass empty array for all scopes.
     *
     * @var array $scope
     * @see https://developer.paypal.com/docs/integration/direct/identity/attributes
     */
    public $scope = [];

    /**
     * The provider api client
     *
     * @var ApiContext $api
     */
    public $api;

    /**
     * TRUE if sandbox mode is ON otherwise FALSE
     *
     * @var bool $sandbox
     */
    public $sandbox = true;

    /**
     * {@inheritdoc}
     */
    function initialize()
    {
        if (!$this->config["keys"]["id"] || !$this->config["keys"]["secret"]) {
            throw new Exception("Your application id and secret are required in order to connect to {$this->providerId}.", 4);
        }

        // Set scope from config.
        if (isset($this->config["scope"])) {
            $scope = $this->config["scope"];
            if (is_string($scope)) {
                $scope = explode(" ", $scope);
            }
            $scope = array_map("trim", $scope);
            $this->scope = $scope;
        }

        // Set sandbox from config.
        if (isset($this->config["sandbox"]) && is_bool($this->config["sandbox"])) {
            $this->sandbox = $this->config["sandbox"];
        }

        // Include 3rd-party SDK.
        $this->autoLoaderInit();

        // Set up ApiContext.
        $this->api = new ApiContext(
            new OAuthTokenCredential(
                $this->config["keys"]["id"],
                $this->config["keys"]["secret"]
            )
        );

        // Set up config.
        $this->api->setConfig(array(
            "log.LogEnabled" => Hybrid_Auth::$config["debug_mode"],
            "log.FileName" => Hybrid_Auth::$config["debug_file"],
            "log.LogLevel" => "DEBUG",
            "http.CURLOPT_SSLVERSION" => CURL_SSLVERSION_TLSv1,
            "mode" => $this->sandbox ? "sandbox" : "live",
        ));
    }

    /**
     * {@inheritdoc}
     */
    function loginBegin()
    {
        $url = OpenIdSession::getAuthorizationUrl(
            $this->endpoint,
            $this->scope,
            null,
            null,
            null,
            $this->api
        );
        // Redirect to PayPal.
        Hybrid_Auth::redirect($url);
    }

    /**
     * {@inheritdoc}
     */
    function loginFinish()
    {
        if (!isset($_GET["code"])) {
            throw new Exception("Authentication failed! User has canceled authentication!", 5);
        }

        $code = $_GET["code"];
        try {
            // Obtain Authorization Code from Code, Client ID and Client Secret
            $accessToken = OpenIdTokeninfo::createFromAuthorizationCode(array("code" => $code), null, null, $this->api);
            if ($accessToken) {
                $this->setUserConnected();

                // Store tokens.
                $this->token("id_token", $accessToken->getIdToken());
                $this->token("access_token", $accessToken->getAccessToken());
                $this->token("refresh_token", $accessToken->getRefreshToken());
            }
        } catch (PayPalConnectionException $e) {
            throw new Hybrid_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    function logout()
    {
        parent::logout();
        if ($idToken = $this->token("id_token")) {
            $url = OpenIdSession::getLogoutUrl(
                $this->params["hauth_return_to"],
                $idToken,
                $this->api
            );
            // Redirect to PayPal.
            Hybrid_Auth::redirect($url);
        }
    }

    /**
     * {@inheritdoc}
     */
    function getUserProfile()
    {
        try {
            $params = array("access_token" => $this->token("access_token"));
            $userInfo = OpenIdUserinfo::getUserinfo($params, $this->api);

            $profile = new Hybrid_User_Profile();

            $profile->identifier = $userInfo->getUserId();
            $profile->firstName = $userInfo->getGivenName();
            $profile->lastName = $userInfo->getFamilyName();
            $profile->displayName = $userInfo->getName();
            $profile->photoURL = $userInfo->getPicture();
            $profile->gender = $userInfo->getGender();
            $profile->email = $userInfo->getEmail();
            $profile->emailVerified = $userInfo->getEmailVerified();
            $profile->language = $userInfo->getLocale();
            $profile->phone = $userInfo->getPhoneNumber();
            if ($address = $userInfo->getAddress()) {
                $profile->address = $address->getStreetAddress();
                $profile->city = $address->getLocality();
                $profile->zip = $address->getPostalCode();
                $profile->country = $address->getCountry();
                $profile->region = $address->getRegion();
            }

            if ($birthdate = $userInfo->getBirthday()) {
                if (strpos($birthdate, "-") === FALSE) {
                    if ($birthdate !== "0000") {
                        $profile->birthYear = (int)$birthdate;
                    }
                } else {
                    list($birthday_year, $birthday_month, $birthday_day) = explode("-", $birthdate);

                    $profile->birthDay = (int) $birthday_day;
                    $profile->birthMonth = (int) $birthday_month;
                    if ($birthday_year !== "0000") {
                        $profile->birthYear = (int) $birthday_year;
                    }
                }
            }

            $this->user->profile = $profile;

            return $this->user->profile;
        } catch (Exception $e) {
            throw new Hybrid_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}
