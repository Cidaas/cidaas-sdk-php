<?php
namespace Cidaas\OAuth2\Client\Provider;

use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Cidaas\OAuth2\Client\Provider\Exception\CidaasIdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class Cidaas extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected $baseUrl;

    protected function domain()
    {
        if (empty($this->baseUrl)) {
            throw new \RuntimeException('Cidaas base url is not specified');
        }

        return $this->baseUrl;
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->domain() . '/oauth2-login/oauth2/authz';
    }

    public function getManagerUserInfo()
    {
        return $this->domain() . '/oauth2-usermanagement/oauth2/user';
    }


    public function getValidateTokenUrl()
    {
        return $this->domain() . '/oauth2-login/oauth2/checktoken';
    }

    public function getBaseAccessTokenUrl(array $params = [])
    {
        return $this->domain() . '/oauth2-login/oauth2/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domain() . '/oauth2-usermanagement/oauth2/userinfo';
    }

    public function getDefaultScopes()
    {
        return ['openid','profile', 'email'];
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $base   = $this->getBaseAuthorizationUrl();
        $params = $this->getAuthorizationParameters($options);
        $query  = $this->getAuthorizationQuery($params);

        return $this->appendQuery($base, $query);
    }


    protected function getAuthorizationParametersNew(array $options){

        if (empty($options['state'])) {
            $options['state'] = $this->getRandomState();
        }

        if (empty($options['scope'])) {
            $options['scope'] = $this->getDefaultScopes();
        }
        print_r($options);
        if (empty($options['response_type'])) {
            $options['response_type'] = 'code';
        }

        $options += [
            'approval_prompt' => 'auto'
        ];

        if (is_array($options['scope'])) {
            $separator = $this->getScopeSeparator();
            $options['scope'] = implode($separator, $options['scope']);
        }

        // Store the state as it may need to be accessed later on.
        $this->state = $options['state'];

        // Business code layer might set a different redirect_uri parameter
        // depending on the context, leave it as-is
        if (!isset($options['redirect_uri'])) {
            $options['redirect_uri'] = $this->redirectUri;
        }

        $options['client_id'] = $this->clientId;

        return $options;
    }


    protected function getAuthorizationHeaders($token = null)
    {
        return ["access_token"=>$token];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            return CidaasIdentityProviderException::fromResponse(
                $response,
                $data['error'] ?: $response->getReasonPhrase()
            );
        }
    }


    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new CidaasResourceOwner($response);
    }

    public function validateToken($access_token){
        $client = new Client();

        $result = $client->get($this->getValidateTokenUrl(),[
            "headers"=>[
                "Content-Type" => "application/json",
                "access_token"=>$access_token
            ]
        ]);

        if($result->getBody()->getContents() == "true"){
            return true;
        }

        return false;
    }

    public function getUserInfoById(AccessToken $token,$user_id){
        $client = new Client();

        $result = $client->get($this->getManagerUserInfo()."/".$user_id,[
            "headers"=>[
                "Content-Type" => "application/json",
                "access_token"=>$token->getToken()
            ]
        ]);

        return $this->createResourceOwner($this->parseResponse($result), $token);

    }
}
