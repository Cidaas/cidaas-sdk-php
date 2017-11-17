<?php
namespace Cidaas\OAuth2\Client\Provider;

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

    public function getManagerUserInfoUrl()
    {
        return $this->domain() . '/oauth2-usermanagement/oauth2/user';
    }

    public function getTokenInfoUrl()
    {
        return $this->domain() . '/token/userinfobytoken';
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


    public function getUpdateTokenUsageUrl()
    {
        return $this->domain() . '/token/updateusage';
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



    public function getUserInfoById(AccessToken $token,$user_id){
        $client = $this->getHttpClient();

        $result = $client->get($this->getManagerUserInfoUrl()."/".$user_id,[
            "headers"=>[
                "Content-Type" => "application/json",
                "access_token"=>$token->getToken()
            ]
        ]);

        return $this->createResourceOwner($this->parseResponse($result), $token);

    }

    public function isTokenExpired($access_token){
        $client = $this->getHttpClient();

        $result = $client->get($this->getValidateTokenUrl(),[
            "headers"=>[
                "Content-Type" => "application/json",
                "access_token"=>$access_token
            ]
        ]);

        if($result->getBody()->getContents() == "true"){
            return false;
        }

        return true;
    }

    public  function  validateToken($pasedInfo=[],$roles=[],$scopes=[]){
        $access_token_key = "access_token";

        if(!isset($pasedInfo[$access_token_key])){
            return [
                "error"=>"Access denied for this resource",
                "status_code"=>401,
                "message" => "Access token cannot be null"
            ];
        }

        if(!isset($pasedInfo["headers"])){
            return [
                "error"=>"Access denied for this resource",
                "status_code"=>401,
                "message" => "Headers cannot be null"
            ];
        }

        $dataToSend = prepareTokenUsageEntity($pasedInfo,$roles,$scopes);
        if($dataToSend == null){
            return [
                "error"=>"Access denied for this resource",
                "status_code"=>401
            ];
        }

        $client = $this->getHttpClient();

        $result = $client->post($this->getTokenInfoUrl(),[
            "json"=>$dataToSend,
            "headers"=>[
                "Content-Type" => "application/json",
                "access_token"=>$pasedInfo[$access_token_key]
            ]
        ]);

        if($result->getStatusCode() == 200) {
            $token_check_response = json_decode($result->getBody()->getContents());

            return [
                "data"=>$token_check_response,
                "status_code"=>200
            ];

        }

        return [
            "error"=>"Access denied for this resource",
            "status_code"=>401
        ];
    }

    public  function  prepareTokenUsageEntity($pasedInfo=[],$roles=[],$scopes=[]){
        $access_token_key = "access_token";
        if(!isset($pasedInfo[$access_token_key])){
            return null;
        }

        if(!isset($pasedInfo["headers"])){
            return null;
        }

        $headers = $pasedInfo["headers"];


        $ipAddress = "";

        if(isset($headers["x-forwarded-for"])){
            $ips = explode(" ",$headers["x-forwarded-for"]);
            if(sizeof($ips)>0){
                $ipAddress = $ips[0];
            }

        }


        $host = "";
        if(isset($headers["X-Forwarded-Host"])){
            $host = $headers["X-Forwarded-Host"];
        }

        $acceptLanguage = "";

        if(isset($headers["Accept-Language"])){
            $acceptLanguage = $headers["Accept-Language"];
        }

        $userAgent = "";

        if(isset($headers["user-agent"])){
            $userAgent = $headers["user-agent"];
        }

        $referrer = "";

        if(isset($headers["referrer"])){
            $referrer = $headers["referrer"];
        }


        $dataToSend = [
            "accessToken"=>$pasedInfo[$access_token_key],
            "userId"=>null,
            "clientId"=>null,
            "referrer"=>$referrer,
            "ipAddress"=>$ipAddress,
            "host"=>$host,
            "acceptLanguage"=>$acceptLanguage,
            "userAgent"=>$userAgent,
            "requestURL"=>isset($pasedInfo["requestURL"])?$pasedInfo["requestURL"]:"",
            "success"=>false,
            "requestedScopes"=>"",
            "requestedRoles"=>"",
            "createdTime"=>date_create('now')->format('Y-m-d\TH:i:sO'),
            "requestInfo"=>$headers
        ];



        if($roles!=null){
            $dataToSend["requestedRoles"] =  implode(",",$roles);
        }

        if($scopes!=null){
            $dataToSend["requestedScopes"] =  implode(" ",$scopes);
        }

        return $dataToSend;

    }

    public  function  updateTokenUsage($preparedTokenList=[]){

        $client = $this->getHttpClient();

        $result = $client->post($this->getUpdateTokenUsageUrl(),[
            "json"=>$preparedTokenList,
            "headers"=>[
                "Content-Type" => "application/json"
            ]
        ]);

        if($result->getStatusCode() == 200) {

            return [
                "status_code"=>200
            ];

        }

        return [
            "error"=>"Access denied for this resource",
            "status_code"=>401
        ];
    }

}
