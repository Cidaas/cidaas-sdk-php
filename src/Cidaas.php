<?php

namespace Cidaas\OAuth2\Client\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Cidaas connector.
 * @package Cidaas\OAuth2\Client\Provider
 */
class Cidaas {
    private static $well_known_uri = "/.well-known/openid-configuration";
    private static $requestIdUri = '/authz-srv/authrequest/authz/generate';
    private static $getRegistrationSetupUri = '/registration-setup-srv/public/list';
    private static $registerSdkUri = '/users-srv/register';
    private static $loginSdkUri = '/login-srv/login/sdk';
    private static $changePasswordUri = '/users-srv/changepassword';
    private static $updateProfileUriPrefix = '/users-srv/user/profile/';
    private static $initiateResetPasswordUri = '/users-srv/resetpassword/initiate';
    private static $handleResetPasswordUri = '/users-srv/resetpassword/validatecode';
    private static $resetPasswordUri = '/users-srv/resetpassword/accept';

    private $loadOpenIdConfigPromise;
    private $openid_config;

    private $baseUrl = "";
    private $clientId = "";
    private $clientSecret = "";
    private $redirectUri = "";
    private $handler;
    private $debug = false;

    /**
     * Cidaas constructor.
     * @param string $baseUrl of cidaas server
     * @param string $cliendId of cidaas application
     * @param string $clientSecret of cidaas application
     * @param string $redirectUri to redirect to after login
     * @param HandlerStack|null $handler (optional) for http requests
     * @param bool $debug (optional) to enable debugging
     */
    public function __construct(string $baseUrl, string $cliendId, string $clientSecret, string $redirectUri, HandlerStack $handler = null, bool $debug = false) {
        $this->validate($baseUrl, '$baseUrl');
        $this->validate($cliendId, '$cliendId');
        $this->validate($clientSecret, '$clientSecret');
        $this->validate($redirectUri, '$redirectUri');

        $this->baseUrl = rtrim($baseUrl, "/");
        $this->clientId = $cliendId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        if (isset($handler)) {
            $this->handler = $handler;
        }
        $this->debug = $debug;

        $this->resolveOpenIDConfiguration();
    }

    /**
     * Retrieve the requestId for a given scope in order to start an oidc interaction.
     * @param string $scope for the requestId
     * @return PromiseInterface promise with the requestId or error
     */
    public function getRequestId($scope = 'openid'): PromiseInterface {
        $client = $this->createClient();

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            "response_type" => "code",
            "scope" => $scope,
            "nonce" => time()
        ];
        $postBody = json_encode($params, JSON_UNESCAPED_SLASHES);
        $options = [
            RequestOptions::BODY => $postBody,
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Accept' => '*/*'
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
     * @return PromiseInterface promise with user data or error
     */
    public function register(array $registrationFields, string $requestId): PromiseInterface {
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
                'requestId' => $requestId
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
     * @param string scope for login
     * @throws \LogicException if no loginUrl has been set
     */
    public function loginWithBrowser(string $scope = 'openid profile offline_access') {
        $loginUrl = $this->getOpenIdConfig()['authorization_endpoint'];
        $loginUrl .= '?client_id=' . $this->clientId;
        $loginUrl .= '&response_type=code';
        $loginUrl .= '&scope=' . urlencode($scope);
        $loginUrl .= '&redirect_uri=' . $this->redirectUri;
        $loginUrl .= '&nonce=' . time();

        header('Location: ' . $loginUrl);
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
        $params = [];
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

        $url = $this->getOpenIdConfig()["token_endpoint"];

        $client = $this->createClient();
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
        $url = $this->getOpenIdConfig()["userinfo_endpoint"];
        if (!empty($sub)) {
            $url .= "/" . $sub;
        }

        $client = $this->createClient();
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
        $url = $this->getOpenIdConfig()["introspection_endpoint"];

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
        $url = $this->getOpenIdConfig()["end_session_endpoint"] . "?access_token_hint=" . $accessToken;

        if (!empty($postLogoutUri)) {
            $url .= "&post_logout_redirect_uri=" . urlencode($postLogoutUri);
        }

        $client = $this->createClient();
        return $client->requestAsync('POST', $url, ['allow_redirects' => false]);
    }

    private function createClient(): Client {
        $client = null;
        if (isset($this->handler)) {
            $client = new Client(['handler' => $this->handler, 'debug' => $this->debug]);
        } else {
            $client = new Client(['debug' => $this->debug]);
        }

        return $client;
    }

    private function parseJson($content): array {
        $content = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException(sprintf("Failed to parse JSON response: %s", json_last_error_msg()));
        }

        return $content;
    }

    private function validate($param, $name) {
        if (!isset($param) || empty($param)) {
            throw new \InvalidArgumentException($name . ' is not specified');
        }
    }

    private function resolveOpenIDConfiguration(): void {
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('Cidaas base url is not specified');
        }

        $openid_configuration_url = $this->baseUrl . self::$well_known_uri;
        $client = $this->createClient();

        $this->loadOpenIdConfigPromise = $client->getAsync($openid_configuration_url)->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            $this->openid_config = $this->parseJson($body);
        });
    }

    private function getOpenIdConfig(): array {
        if (!isset($this->openid_config)) {
            $this->loadOpenIdConfigPromise->wait();
        }
        return $this->openid_config;
    }
}
