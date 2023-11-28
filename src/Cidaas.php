<?php

namespace Cidaas\OAuth2\Client\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

/**
 * Cidaas connector.
 * @package Cidaas\OAuth2\Client\Provider
 */
class Cidaas {
    private static string $well_known_uri = "/.well-known/openid-configuration";
    private static string $requestIdUri = '/authz-srv/authrequest/authz/generate';
    private static string $getRegistrationSetupUri = '/registration-setup-srv/public/list';
    private static string $registerSdkUri = '/users-srv/register';
    private static string $loginSdkUri = '/login-srv/login/sdk';
    private static string $changePasswordUri = '/users-srv/changepassword';
    private static string $updateProfileUriPrefix = '/users-srv/user/profile/';
    private static string $initiateResetPasswordUri = '/users-srv/resetpassword/initiate';
    private static string $handleResetPasswordUri = '/users-srv/resetpassword/validatecode';
    private static string $resetPasswordUri = '/users-srv/resetpassword/accept';
    private static string $tokenUri = '/token-srv/token';

    private array $openid_config;
    private string $baseUrl = "";
    private string $clientId = "";
    private string $clientSecret = "";
    private string $redirectUri = "";
    private HandlerStack $handler;
    private bool $debug = false;
    /** @var bool has the init method already been called? */
    private bool $init = false;

    /**
     * Cidaas constructor.
     * @param string $baseUrl of cidaas server
     * @param string $clientId of cidaas application
     * @param string $clientSecret of cidaas application
     * @param string $redirectUri to redirect to after login
     * @param HandlerStack|null $handler (optional) for http requests
     * @param bool $debug (optional) to enable debugging
     */
    public function __construct(string $baseUrl, string $clientId, string $clientSecret, string $redirectUri, HandlerStack $handler = null, bool $debug = false) {
        $this->validate($baseUrl, 'Base URL');
        $this->validate($clientId, 'Client-ID');
        $this->validate($clientSecret, 'Client-Secret');
        $this->validate($redirectUri, 'Redirect URL');

        $this->baseUrl = rtrim($baseUrl, "/");
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        if (isset($handler)) {
            $this->handler = $handler;
        }
        $this->debug = $debug;

    }

    /**
     * loads the OpenID config from the server the first time the client is used.
     *
     * @return void
     */
    private function initClient()
    {
        if($this->init)
        {
            return;
        }
        $this->openid_config = $this->loadOpenIdConfig();
        $this->init = true;
    }


    /**
     * Retrieve the requestId for a given scope in order to start an oidc interaction.
     * @param string $scope for the requestId
     * @param string $responseType for the response type
     * @param string $acceptLanguage for the language. defaults to "en-GB"
     * @return PromiseInterface promise with the requestId or error
     */
    public function getRequestId(string $scope = 'openid', string $responseType = 'code', string $acceptLanguage = 'en-GB'): PromiseInterface {
        $client = $this->createClient();

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            "response_type" => $responseType,
            "scope" => $scope,
            "nonce" => (string)time()
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                'accept-language' => $acceptLanguage
            ]
        ];
        $url = $this->baseUrl . self::$requestIdUri;
        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            $parsedBody = $this->parseJson($body);
            return $parsedBody['data']['requestId'];
        });
    }

    /**
     * Retrieves registration data for dynamic implementation of registration page.
     * @param string $requestId for retrieving registration data
     * @param string $locale for registration data
     * @return PromiseInterface promise with registration data or error
     */
    public function getRegistrationSetup(string $requestId, string $locale): PromiseInterface {
        $client = $this->createClient();

        $url = $this->baseUrl . self::$getRegistrationSetupUri;
        $params = [
            'requestId' => $requestId,
            'acceptlanguage' => $locale
        ];
        $options = [
            RequestOptions::QUERY => $params
        ];

        $responsePromise = $client->getAsync($url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Register new user.
     * @param array $registrationFields containing user data according to {@see getRegistrationSetup()}
     * @param string $requestId for user registration
     * @param string $acceptLanguage for the language. defaults to "en-GB"
     * @return PromiseInterface promise with user data or error
     */
    public function register(array $registrationFields, string $requestId, string $acceptLanguage = 'en-GB'): PromiseInterface {
        $client = $this->createClient();

        $url = $this->baseUrl . self::$registerSdkUri;
        if (!isset($registrationFields['provider'])) {
            $registrationFields['provider'] = 'self';
        }
        $postBody = json_encode($registrationFields, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'requestId' => $requestId,
                'accept-language' => $acceptLanguage
            ],
        ];

        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Performs a login with the given credentials.
     * @param string $username for login
     * @param string $username_type of $username
     * @param string $password for login
     * @param string $requestId for login request
     * @return PromiseInterface promise with code or error
     */
    public function loginWithCredentials(string $username, string $username_type, string $password, string $requestId): PromiseInterface {
        $client = $this->createClient();
        $url = $this->baseUrl . self::$loginSdkUri;
        $params = [
            'username' => $username,
            'password' => $password,
            'username_type' => $username_type,
            'requestId' => $requestId
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json'
            ]
        ];

        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Performs a redirect to the hosted login page.
     * @param string $scope for login
     * @param array $queryParameters (optional) optionally adds more query parameters to the url.
     * @throws LogicException if no loginUrl has been set
     */
    public function loginWithBrowser(string $scope = 'openid profile offline_access', array $queryParameters = array()) {
        $loginUrl = $this->openid_config['authorization_endpoint'];
        $loginUrl .= '?client_id=' . $this->clientId;
        $loginUrl .= '&response_type=code';
        $loginUrl .= '&scope=' . urlencode($scope);
        $loginUrl .= '&redirect_uri=' . $this->redirectUri;
        $loginUrl .= '&nonce=' . time();
        $loginUrl .= '&view_type=' . "login";
        foreach ($queryParameters as $key => $value) {
            $loginUrl .= '&' . $key . '=' . $value;
        }
        header('Location: ' . $loginUrl);
    }

    /**
     * Performs a redirect to the hosted registration page.
     * @param string $scope for registration
     * @param array $queryParameters (optional) optionally adds more query parameters to the url.
     */
    public function registerWithBrowser(string $scope = 'openid profile offline_access', array $queryParameters = array()) {
        $this->initClient();
        $registerUrl = $this->openid_config['authorization_endpoint'];
        $registerUrl .= '?client_id=' . $this->clientId;
        $registerUrl .= '&response_type=code';
        $registerUrl .= '&scope=' . urlencode($scope);
        $registerUrl .= '&redirect_uri=' . $this->redirectUri;
        $registerUrl .= '&nonce=' . time();
        $registerUrl .= '&view_type=' . "register";
        foreach ($queryParameters as $key => $value) {
            $registerUrl .= '&' . $key . '=' . $value;
        }
        header('Location: ' . $registerUrl);
    }

    /**
     * Returns all query parameters from get request. The result should contain 'code' to be used for retrieving access token.
     * @return array with $_GET
     */
    public function loginCallback(): array {
        return $_GET;
    }

    /**
     * Change a password of a given identity.
     *
     * @param string $oldPassword of the identity
     * @param string $newPassword of the identity
     * @param string $confirmPassword to match with newPassword above
     * @param string $identityId to identify user
     * @param string $accessToken for access to password change api
     * @return PromiseInterface with promise containing success or error message
     */
    public function changePassword(string $oldPassword, string $newPassword, string $confirmPassword, string $identityId, string $accessToken) {
        $client = $this->createClient();

        $params = [
            'old_password' => $oldPassword,
            'new_password' => $newPassword,
            'confirm_password' => $confirmPassword,
            'identityId' => $identityId
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ]
        ];
        $url = $this->baseUrl . self::$changePasswordUri;

        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Retrieve access token for api access.
     * @param string $grantType for accessToken {@see GrantType}
     * @param string $code only required for {@see GrantType::$AuthorizationCode}
     * @param string $refreshToken only required for {@see GrantType::$RefreshToken}
     * @return PromiseInterface promise with access token or error
     */
    public function getAccessToken(string $grantType, string $code = '', string $refreshToken = ''): PromiseInterface {
        if ($grantType === GrantType::AuthorizationCode) {
            if (empty($code)) {
                throw new \InvalidArgumentException('code must not be empty in authorization_code flow');
            }

            $params = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $code,
            ];
        } else if ($grantType === GrantType::RefreshToken) {
            if (empty($refreshToken)) {
                throw new \InvalidArgumentException('refreshToken must not be empty in refresh_token flow');
            }
            $params = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ];
        } else if ($grantType === GrantType::ClientCredentials) {
            $params = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ];
        } else {
            throw new \InvalidArgumentException('invalid grant type');
        }

        $client = $this->createClient();
        $url = $this->baseUrl . self::$tokenUri;
        $responsePromise = $client->requestAsync('POST', $url, ['form_params' => $params]);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Retrieve user profile of current user or a given sub.
     * @param string $accessToken to access api
     * @param string $sub (optional) for user profile to retrieve
     * @return PromiseInterface promise with user profile or error
     */
    public function getUserProfile(string $accessToken, string $sub = ""): PromiseInterface {
        $client = $this->createClient();
        $url = $this->openid_config["userinfo_endpoint"];
        if (!empty($sub)) {
            $url .= "/" . $sub;
        }

        $responsePromise = $client->requestAsync('POST', $url, [
            "headers" => [
                "Authorization" => "Bearer " . $accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Start a password reset process.
     * @param string $email address for password reset
     * @param string $requestId for api access
     * @return PromiseInterface promise with resetRequestId or error
     */
    public function initiateResetPassword(string $email, string $requestId): PromiseInterface {
        // TODO andere Medien statt email?
        $client = $this->createClient();

        $params = [
            'email' => $email,
            'processingType' => 'CODE',
            'resetMedium' => 'email',
            'requestId' => $requestId
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json'
            ]
        ];
        $url = $this->baseUrl . self::$initiateResetPasswordUri;

        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Verify code sent during {@see self::initiateResetPassword()}.
     * @param string $code sent by password reset method
     * @param string $resetRequestId retrieved from {@see self::initiateResetPassword()}
     * @return PromiseInterface promise with exchangeId and resetRequestId or error
     */
    public function handleResetPassword(string $code, string $resetRequestId): PromiseInterface {
        $client = $this->createClient();

        $params = [
            'code' => $code,
            'resetRequestId' => $resetRequestId
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json'
            ]
        ];
        $url = $this->baseUrl . self::$handleResetPasswordUri;

        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Perform password reset.
     * @param string $password to set
     * @param string $confirmPassword to confirm $password
     * @param string $exchangeId from {@see self::handleResetPassword()}
     * @param string $resetRequestId from {@see self::handleResetPassword()}
     * @return PromiseInterface promise with success or error message
     */
    public function resetPassword(string $password, string $confirmPassword, string $exchangeId, string $resetRequestId): PromiseInterface {
        $client = $this->createClient();

        $params = [
            'password' => $password,
            'confirmPassword' => $confirmPassword,
            'exchangeId' => $exchangeId,
            'resetRequestId' => $resetRequestId
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json'
            ]
        ];
        $url = $this->baseUrl . self::$resetPasswordUri;

        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Update user profile.
     * @param string $sub of profile to be updated
     * @param array $fields to update
     * @param string $accessToken for api access
     * @param string $provider of identity profile
     * @return PromiseInterface promise with success or error message
     */
    public function updateProfile(string $sub, array $fields, string $accessToken, string $provider = 'self'): PromiseInterface {
        $client = $this->createClient();

        $fields['provider'] = $provider;
        $postBody = json_encode($fields, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken
            ]
        ];
        $url = $this->baseUrl . self::$updateProfileUriPrefix . $sub;

        $responsePromise = $client->requestAsync('PUT', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Validates a given access token.
     * @param string $accessTokenToValidate to validate
     * @param string $accessTokenForApiAccess to access api
     * @return PromiseInterface with success or error message
     */
    public function validateAccessToken(string $accessTokenToValidate, $accessTokenForApiAccess = ""): PromiseInterface {
        $client = $this->createClient();

        $params = [
            'token_type_hint' => 'access_token',
            'token' => $accessTokenToValidate
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $headers = ['Content-Type' => 'application/json'];
        if (empty($accessTokenForApiAccess)) {
            $headers['Authorization'] = 'Basic ' . base64_encode($this->clientId . ":" . $this->clientSecret);
        } else {
            $headers['Authorization'] = 'Bearer ' . $accessTokenForApiAccess;
        }
        $options = [
            RequestOptions::HEADERS => $headers,
            RequestOptions::BODY => $postBody
        ];
        $url = $this->openid_config["introspection_endpoint"];

        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    /**
     * Perform logout at server. Please note, that this method does not perform a redirect to logout page, but returns the response, if logout is completed.
     * @param string $accessToken for api access
     * @param string $postLogoutUri (optional) to redirect to after logout
     * @return PromiseInterface promise with success (redirect) or error message
     */
    public function logout(string $accessToken, string $postLogoutUri = ""): PromiseInterface {
        $client = $this->createClient();
        $url = $this->openid_config["end_session_endpoint"] . "?access_token_hint=" . $accessToken;
        if (!empty($postLogoutUri)) {
            $url .= "&post_logout_redirect_uri=" . urlencode($postLogoutUri);
        }

        return $client->requestAsync('POST', $url, ['allow_redirects' => false]);
    }

    /**
     * Performs a redirect to the login page of the social provider
     * @param string $provider_name name of the social provider. e.g: google
     * @param string $request_id the request_id of the oidc session. once can generate a request_id by calling the function getRequestId
     * @param array $queryParameters (optional) optionally adds more query parameters to the url.
     */
    public function loginWithSocial(string $provider_name, string $request_id, array $queryParameters = array()) {
        $url = $this->baseUrl. "/login-srv/social/login/". strtolower($provider_name) . "/" . $request_id;
        foreach ($queryParameters as $key => $value) {
            $registerUrl .= '&' . $key . '=' . $value;
        }
        header('Location: ' . $url);
    }

     /**
     * Performs a redirect to the register page of the social provider
     * @param string $provider_name name of the social provider. e.g: google
     * @param string $request_id the request_id of the oidc session. once can generate a request_id by calling the function getRequestId
     * @param array $queryParameters (optional) optionally adds more query parameters to the url.
     */
    public function registerWithSocial($provider_name, $request_id, array $queryParameters = array()) {
        $url = $this->baseUrl. "/login-srv/social/register/". strtolower($provider_name) . "/" . $request_id;
        foreach ($queryParameters as $key => $value) {
            $registerUrl .= '&' . $key . '=' . $value;
        }
        header('Location: ' . $url);
    }

     /**
     * Initiates multi factore authentication
     * @param string $type the type of multi factor. e.g: email, sms
     * @param array $params an associate array with the params that api accepts as request body
     */
    public function intiateMFA($type, $params) {
        $url = $this->baseUrl."/verification-srv/v2/authenticate/initiate/". strtolower($type);
        return $this->makeRequest($params, $url);
    }

    /**
     * Validates multi factore authentication
     * @param string $exchange_id the exchange_id received in the response body while initiating mfa
     * @param string $sub the sub received in the response body while initiating mfa
     * @param string $pass_code the verification code recieved to the prefered mfa type
     * @param string $type the type of multi factor. e.g: email, sms
     */
    public function authenticateMFA(string $exchange_id, string $sub, string $pass_code, string $type) {
        $url = $this->baseUrl . "/verification-srv/v2/authenticate/authenticate/". strtolower($type);
        $allowed_types = ["EMAIL", "SMS"];
        if (!in_array(strtoupper($type), $allowed_types)) {
            throw new \InvalidArgumentException('invalid mfa type');
        }
        $params = [
            'exchange_id' => $exchange_id,
            'sub' => $sub,
            'type' => strtoupper($type),
            'pass_code' => $pass_code
        ];
       return $this->makeRequest($params, $url);
    }

    /**
     * Initiates account verification
     * @param array $params an associate array with the params that api accepts as request body
    */
    public function initiateAccountVerification($params) {
        $url = $this->baseUrl . "/verification-srv/account/initiate";
        return $this->makeRequest($params, $url);
    }

    /**
     * Verify account
     * @param string $accvid accvid recieved when initiating the account verification
     * @param array $code code received to the users account to verify after registration
    */
    public function verifyAccount(string $accvid, string $code) {
        $url = $this->baseUrl . "/verification-srv/account/verify";
        $params = [
            'accvid' => $accvid,
            'code' => $code
        ];
        return $this->makeRequest($params, $url);
    }

    /**
     * Initiates progressive registration for missing required registration fields after an account is created in cidaas system
     * @param string $request_id the request_id of the oidc session. once can generate a request_id by calling the function getRequestId
     * @param string $trackId the track_id recieved of the oidc session
     * @param string $acceptLanguage the locale
     * @param array $params an associate array with the params that api accepts as request body
    */
    public function progressiveRegistration($requestId, $trackId, $params, $acceptLanguage = 'en-US') {
        $url = $this->baseUrl . "/login-srv/progressive/update/user";
        $headers = [
            'Content-type' => 'application/json',
            'requestId' => $requestId,
            'trackId' => $trackId,
            'acceptlanguage' => $acceptlanguage
        ];
        return $this->makeRequest($params, $url, $headers);
    }

    /**
     * Provides the details of an consent
     * @param string $consent_id the id of the consent
     * @param string $consent_version_id the version of the consent
     * @param string $sub the sub
    */
    public function getConsentDetails($consent_id, $consent_version_id, $sub){
        $url = $this->baseUrl . "/consent-management-srv/v2/consent/usage/public/info";
        $params = [
            'consent_id' => $consent_id,
            'consent_version_id' => $consent_version_id,
            'sub' => $sub
        ];
        return $this->makeRequest($params, $url);
    }

    /**
     * To accept a consent
     * @param array $params an associate array with the params that api accepts as request body
    */
    public function acceptConsent($params){
        $url = $this->baseUrl . "/consent-management-srv/v2/consent/usage/accept";
        return $this->makeRequest($params, $url);
    }

    /**
     * Get the list of the mfa configured for an user. One of email,mobile_number,username or sub must be provided
     * @param string $request_id the request_id of the oidc session. once can generate a request_id by calling the function getRequestId
     * @param string $email (optional) the email of the user
     * @param string $mobile_number (optional) the mobile number of the user
     * @param string $username (optional) the username of the user
     * @param string $sub (optional) the uniqe identified of the user
    */
    public function getMFAList(string $request_id, string $email = '', string $mobile_number = '', string $username = '', string $sub = '') {
        $url = $this->baseUrl . "/verification-srv/v2/setup/public/configured/list";
        $params = [
            'request_id' => $request_id,
            'sub' => $sub,
            'email' => $email,
            'mobile_number' => $mobile_number,
            'username' => $username
        ];
        return $this->makeRequest($params, $url);
    }

    /**
     * Perform login without password. E.g: totp, backupcode
     * @param string $request_id the request_id of the oidc session. once can generate a request_id by calling the function getRequestId
     * @param string $email the email of the user
     * @param string $medium_id the medium id of the preferred type. E.g: for type totp the medium id is "TOTP"
     * @param string $type the preferred type of passwordless login. E.g: email, totp, push etc.
    */
    public function initiatePasswordlessLogin(string $request_id, string $type, string $email, string $medium_id) {
        $allowed_types = ["email", "totp", "push", "backup_code", "password"];
        if (!in_array(strtoupper($type), $allowed_types)) {
            throw new \InvalidArgumentException('invalid type');
        }
        $url = $this->baseUrl. "/verification-srv/v2/authenticate/initiate/". strtolower($type);
        $params = [
            'usage_type' => "PASSWORDLESS_AUTHENTICATION",
            'request_id' => $request_id,
            'type' => $type,
            'email' => $email,
            'medium_id' => $medium_id
        ];
        return $this->makeRequest($params, $url);
    }

    /**
     * Perform login without password. E.g: totp, backupcode
     * @param string $requestId the request_id of the oidc session. once can generate a request_id by calling the function getRequestId
     * @param string $pass_code the verification code to validate the login flow
     * @param string $exchange_id the exchage id receieved in the response payload when password login was initiated
     * @param string $type the preferred type of passwordless login. E.g: email, totp, push etc.
    */
    public function verifyPasswordlessLogin(string $type, string $exchange_id, string $pass_code, string $requestId) {
        $allowed_types = ["email", "totp", "push", "backup_code", "password"];
        if (!in_array(strtoupper($type), $allowed_types)) {
            throw new \InvalidArgumentException('invalid type');
        }
        $url = $this->baseUrl. "/verification-srv/v2/authenticate/authenticate/". strtolower($type);
        $params = [
            'exchange_id' => $exchange_id,
            'pass_code' => $pass_code,
            'type' => $type,
            'requestId' => $requestId,
        ];
        return $this->makeRequest($params, $url);
    }

    private function createClient(): Client
    {
        $this->initClient();
        return $this->__createClient();
    }

    private function __createClient(): Client
    {
        if (isset($this->handler)) {
            return new Client(['handler' => $this->handler, 'debug' => $this->debug]);
        }
        return new Client(['debug' => $this->debug]);
    }

    private function parseJson($content): array {
        $content = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException(sprintf("Failed to parse JSON response: %s", json_last_error_msg()));
        }

        return $content;
    }

    private function validate($param, $name) {
        if (empty($param)) {
            throw new \InvalidArgumentException($name . ' is not specified');
        }
    }

    private function loadOpenIdConfig(): array {
        $openid_configuration_url = $this->baseUrl . self::$well_known_uri;
        $client = $this->__createClient();
        return $client->getAsync($openid_configuration_url)->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        })->wait();
    }

    private function makeRequest($params, $url, $headers = ['Content-Type' => 'application/json']) {
        $client = $this->createClient();
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => $headers
        ];
        $responsePromise = $client->requestAsync('POST', $url, $options);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }
}
