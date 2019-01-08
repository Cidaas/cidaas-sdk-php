<?php
namespace Cidaas\OAuth2\Client\Provider;

use GuzzleHttp\Client;

class AbstractProvider
{
    protected $base_url = "";

    private $well_known_url = "/.well-known/openid-configuration";

    private $openid_config;

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

        $this->resolveOpenIDConfiguration();

    }

    private function getBaseURL()
    {
        if (empty($this->base_url)) {
            throw new \RuntimeException('Cidaas base url is not specified');
        }
        return $this->base_url;
    }

    private function resolveOpenIDConfiguration()
    {
        if (empty($this->base_url)) {
            throw new \RuntimeException('Cidaas base url is not specified');
        }

        $client = new Client();

        $openid_configuration_url = $this->getBaseURL() . $this->well_known_url;

        $response = $client->get($openid_configuration_url);

        $body = $response->getBody();

        $this->openid_config = $this->parseJson($body);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }

        $url = $this->openid_config["authorization_endpoint"];

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

    public function getAccessToken($grant, array $options = [])
    {
        $params = [];
        if ($grant == 'authorization_code') {
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

        } else if ($grant == 'refresh_token') {
            if (empty($options['refresh_token'])) {
                throw new \RuntimeException('refresh_token must not be empty in refresh_token flow');
            }

            $params = [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $options['refresh_token'],
            ];

        } else if ($grant == 'client_credentials') {

            $params = [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'client_credentials',
            ];

        } else {
            throw new \RuntimeException('in valid grant');
        }

        $client = new Client();

        $url = $this->openid_config["token_endpoint"];

        $response = $client->request('POST', $url, [
            'form_params' => $params,
        ]);

        $body = $response->getBody();
        return $this->parseJson($body);

    }

    public function getUserInfo($access_token, $sub = "")
    {

        if (empty($access_token)) {
            throw new \RuntimeException('access_token must not be empty');
        }

        $url = $this->openid_config["userinfo_endpoint"];

        if (!empty($sub)) {
            $url = $url . "/" . $sub;
        }

        $client = new Client();

        $response = $client->request('POST', $url, [
            "headers" => [
                "Authorization" => "Bearer " . $access_token,
                'Content-Type' => 'application/json',
            ],
        ]);

        $body = $response->getBody();

        // echo $body;

        return $this->parseJson($body);

    }

    public function introspectToken(array $options = [], $access_token = "")
    {

        if (empty($options["token"])) {
            throw new \RuntimeException('token must not be empty');
        }
        if (empty($options["token_type_hint"])) {
            $options["token_type_hint"] = "access_token";
        }

        $authHeader;
        if (!empty($access_token)) {
            $authHeader = "Bearer " . $access_token;
        } else {
            $authHeader = "Basic " . base64_encode($this->client_id . ":" . $this->client_secret);
        }

        if (empty($authHeader)) {
            throw new \RuntimeException('auth must not be empty');
        }

        $url = $this->openid_config["introspection_endpoint"];

        $client = new Client();

        $response = $client->request('POST', $url, [
            "headers" => [
                "Authorization" => $authHeader,
                'Content-Type' => 'application/json',
            ],
            "json" => $options,
        ]);

        $body = $response->getBody();

        // echo $body;

        return $this->parseJson($body);

    }

    public function endSessionURL($access_token_hint = "", $post_logout_redirect_uri = "")
    {
        $url = $this->openid_config["end_session_endpoint"];

        if (!isset($access_token_hint)) {
            $access_token_hint = "";
        }
        if (!isset($post_logout_redirect_uri)) {
            $post_logout_redirect_uri = "";
        }

        $target_url = $url . "?access_token_hint=" . $access_token_hint;

        if (!empty($post_logout_redirect_uri)) {
            $target_url = $url . "?post_logout_redirect_uri=" . urlencode(post_logout_redirect_uri);
        }

        return $target_url;
    }

    protected function getRandomState($length = 32)
    {
        // Converting bytes to hex will always double length. Hence, we can reduce
        // the amount of bytes by half to produce the correct length.
        return bin2hex(random_bytes($length / 2));
    }

    protected function appendQuery($url, array $queryArray = [])
    {
        $queryString = "";
        foreach ($queryArray as $key => $value) {
            $queryString = $queryString . $key . "=" . urlencode($value) . '&';
        }
        $queryString = rtrim($queryString, "&");

        if ($queryString) {
            $glue = strstr($url, '?') === false ? '?' : '&';
            return $url . $glue . $queryString;
        }

        return $url;
    }

    protected function parseJson($content)
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
