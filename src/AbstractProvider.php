<?php

namespace Cidaas\OAuth2\Client\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use http\Exception\UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;

class AbstractProvider
{
    protected $base_url = "";

    private $well_known_url = "/.well-known/openid-configuration";

    private $requestIdUri = '/authz-srv/authrequest/authz/generate';
    private $loginSdkUri = '/login-srv/login/sdk';
    private $loginUri = '/login-srv/login';

    private $loadOpenIdConfigPromise;
    private $openid_config;

    private $handler;
    private $debug = false;

    private $client_id = "";
    private $client_secret = "";
    private $redirect_uri = "";

    public function __construct(array $options = [])
    {
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }

        if (empty($options["base_url"])) {
            throw new \RuntimeException('base_url is not specified');
        }

        $this->base_url = rtrim($options["base_url"], "/");

        $this->client_id = $options["client_id"];
        $this->client_secret = $options["client_secret"];
        if (isset($options["redirect_uri"])) {
            $this->redirect_uri = $options["redirect_uri"];
        }

        if (isset($options["handler"])) {
            $this->handler = $options["handler"];
        }

        if (isset($options['debug'])) {
            $this->debug = $options['debug'];
        }

        $this->resolveOpenIDConfiguration();
    }

    private function getBaseURL(): string
    {
        if (empty($this->base_url)) {
            throw new \RuntimeException('Cidaas base url is not specified');
        }
        return $this->base_url;
    }

    public function getRequestId(): PromiseInterface
    {
        $client = $this->createClient();

        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            "response_type" => "code",
            "scope" => "openid identities",
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
        $url = $this->getBaseURL() . $this->requestIdUri;
        $responsePromise = $client->requestAsync('POST', $url, $options);

        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            $parsedBody = $this->parseJson($body);
            return $parsedBody['data']['requestId'];
        });
    }

    public function loginWithCredentials($username, $username_type, $password, $requestId): PromiseInterface
    {
        $client = $this->createClient();

        $url = $this->getBaseURL() . $this->loginSdkUri;
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
                'Content-Type' => 'application/json',
                // 'Accept' => '*/*'
            ]
        ];
        $responsePromise = $client->requestAsync('POST', $url, $options);

        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }


    private function resolveOpenIDConfiguration(): void
    {
        if (empty($this->base_url)) {
            throw new \RuntimeException('Cidaas base url is not specified');
        }

        $openid_configuration_url = $this->getBaseURL() . $this->well_known_url;
        $client = $this->createClient();

        $this->loadOpenIdConfigPromise = $client->getAsync($openid_configuration_url)->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            $this->openid_config = $this->parseJson($body);
        });
    }

    private function getOpenIdConfig(): array
    {
        if (!isset($this->openid_config)) {
            $this->loadOpenIdConfigPromise->wait();
        }
        return $this->openid_config;
    }

    protected function getAuthorizationUrl(array $options = []): string
    {
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }

        $url = $this->getOpenIdConfig()["authorization_endpoint"];

        if (!empty($options["scope"])) {
            $scope = $options["scope"];
            $scopes = explode(" ", $scope);
            if (in_array("openid", $scopes)) {
                if (empty($options["nonce"])) {
                    $options["nonce"] = $this->getRandomState();
                }
            }
        }
        if (empty($options["state"])) {
            $options["state"] = $this->getRandomState();
        }
        if (empty($options["response_type"])) {
            $options["response_type"] = "code";
        }
        $options["client_id"] = $this->client_id;

        if (empty($options["redirect_uri"])) {
            $options["redirect_uri"] = $this->redirect_uri;
        }

        return $this->appendQuery($url, $options);
    }

    public function getAccessToken(string $grant_type, array $options = []): PromiseInterface
    {
        $params = [];
        if ($grant_type === GrantType::AuthorizationCode) {
            if (empty($options['code'])) {
                throw new \RuntimeException('code must not be empty in authorization_code flow');
            }

            $params = [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code',
                'code' => $options['code'],
            ];
        } else if ($grant_type === GrantType::RefreshToken) {
            if (empty($options['refresh_token'])) {
                throw new \RuntimeException('refresh_token must not be empty in refresh_token flow');
            }
            $params = [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $options['refresh_token'],
            ];
        } else if ($grant_type === GrantType::ClientCredentials) {
            $params = [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'client_credentials',
            ];
        } else {
            throw new \RuntimeException('invalid grant type');
        }

        $client = $this->createClient();

        $url = $this->getOpenIdConfig()["token_endpoint"];

        $responsePromise = $client->requestAsync('POST', $url, [
            'form_params' => $params,
        ]);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    public function getUserInfo($access_token, $sub = ""): PromiseInterface
    {
        if (empty($access_token)) {
            throw new \RuntimeException('access_token must not be empty');
        }

        $url = $this->getOpenIdConfig()["userinfo_endpoint"];

        if (!empty($sub)) {
            $url = $url . "/" . $sub;
        }

        $client = $this->createClient();

        $responsePromise = $client->requestAsync('POST', $url, [
            "headers" => [
                "Authorization" => "Bearer " . $access_token,
                'Content-Type' => 'application/json',
            ],
        ]);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();

            return $this->parseJson($body);
        });
    }

    public function introspectToken(array $options = [], $access_token = ""): PromiseInterface
    {
        if (empty($options["token"])) {
            throw new \RuntimeException('token must not be empty');
        }
        if (empty($options["token_type_hint"])) {
            $options["token_type_hint"] = "access_token";
        }

        $authHeader = "";
        if (!empty($access_token)) {
            $authHeader = "Bearer " . $access_token;
        } else {
            $authHeader = "Basic " . base64_encode($this->client_id . ":" . $this->client_secret);
        }

        if (empty($authHeader)) {
            throw new \RuntimeException('auth must not be empty');
        }

        $url = $this->getOpenIdConfig()["introspection_endpoint"];

        $client = $this->createClient();

        $responsePromise = $client->requestAsync('POST', $url, [
            "headers" => [
                "Authorization" => $authHeader,
                'Content-Type' => 'application/json',
            ],
            "json" => $options,
        ]);
        return $responsePromise->then(function (ResponseInterface $response) {
            $body = $response->getBody();
            return $this->parseJson($body);
        });
    }

    public function endSessionURL($access_token_hint = "", $post_logout_redirect_uri = ""): string
    {
        $url = $this->getOpenIdConfig()["end_session_endpoint"];

        $target_url = $url . "?access_token_hint=" . $access_token_hint;

        if (!empty($post_logout_redirect_uri)) {
            $target_url = $target_url . "&post_logout_redirect_uri=" . urlencode($post_logout_redirect_uri);
        }

        return $target_url;
    }

    private function createClient(): Client
    {
        $client = null;
        if (isset($this->handler)) {
            $client = new Client(['handler' => $this->handler, 'debug' => $this->debug]);
        } else {
            $client = new Client(['debug' => $this->debug]);
        }

        return $client;
    }

    protected function getRandomState($length = 32): string
    {
        // Converting bytes to hex will always double length. Hence, we can reduce
        // the amount of bytes by half to produce the correct length.
        return bin2hex(random_bytes($length / 2));
    }

    protected function appendQuery($url, array $queryArray = []): string
    {
        $queryString = "";
        foreach ($queryArray as $key => $value) {
            $queryString = $queryString . $key . "=" . urlencode($value) . '&';
        }
        $queryString = rtrim($queryString, "&");

        if ($queryString) {
            $glue = strpos($url, '?') === false ? '?' : '&';
            return $url . $glue . $queryString;
        }

        return $url;
    }

    protected function parseJson($content): array
    {
        $content = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException(sprintf(
                "Failed to parse JSON response: %s",
                json_last_error_msg()
            ));
        }

        return $content;
    }
}
