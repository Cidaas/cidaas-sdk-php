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
        $client = $this->getHttpClient();

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
        $client = $this->getHttpClient();

        $result = $client->get($this->getManagerUserInfoUrl()."/".$user_id,[
            "headers"=>[
                "Content-Type" => "application/json",
                "access_token"=>$token->getToken()
            ]
        ]);

        return $this->createResourceOwner($this->parseResponse($result), $token);

    }

    public  function  validateAccessByToken($pasedInfo=[],$roles=[],$scopes=[]){
        $access_token_key = "access_token";
        if($pasedInfo[$access_token_key] == null){
            return [
                "error"=>"Access denied for this resource",
                "status_code"=>401,
                "message" => "Access token cannot be null"
            ];
        }

        $dataToSend = [
            "accessToken"=>$pasedInfo[$access_token_key],
            "userId"=>null,
            "clientId"=>null,
            "referrer"=>$pasedInfo["referrer"],
            "ipAddress"=>$pasedInfo["ipAddress"],
            "host"=>$pasedInfo["host"],
            "acceptLanguage"=>$pasedInfo["acceptLanguage"],
            "userAgent"=>$pasedInfo["userAgent"],
            "requestURL"=>$pasedInfo["requestURL"],
            "success"=>false,
            "requestedScopes"=>"",
            "requestedRoles"=>"",
            "createdTime"=>date_create('now')->format('Y-m-d\TH:i:sO'),
            "requestInfo"=>$pasedInfo
        ];


        if($roles!=null){
            $dataToSend["requestedRoles"] =  implode(",",$roles);
        }

        if($scopes!=null){
            $dataToSend["requestedScopes"] =  implode(" ",$scopes);
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
//
//    public function getUserInfo(Request $request){
//
//        $access_token_key = "access_token";
//
//        $access_token = null;
//
//        if( $request->headers->has($access_token_key)) {
//            $access_token = $request->headers->get($access_token_key);
//        }
//
//        if($access_token==null && $request->query($access_token_key) != null){
//            $access_token = $request->query($access_token_key);
//        }
//
//        if($access_token==null && $request->headers->has("authorization")){
//            $auth = $request->headers->get("authorization");
//
//            if(strtolower(substr($auth, 0, strlen("bearer"))) === "bearer"){
//                $authvals = explode(" ",$auth);
//
//                if(sizeof($authvals)>1){
//                    $access_token = $authvals[1];
//                }
//            }
//        }
//
//        $cookieRequest = false;
//        if($access_token==null && $request->cookies->get("access_token")){
//            $access_token = $request->cookies->get("access_token");
//            $cookieRequest = true;
//        }
//
//
//        if($access_token == null)
//        {
//            return [
//                "error"=>"Access denied for this resource",
//                "status_code"=>401
//            ];
//        }
//
//
//
//        $ipAddress = "";
//
//        if($request->headers->has("x-forwarded-for")){
//            $ips = explode(" ",$request->headers->get("x-forwarded-for"));
//            $ipAddress = explode(",",$auth)[0];
//        }
//
//
//        $host = "";
//
//        if($request->headers->has("X-Forwarded-Host")){
//            $host = $request->headers->get("X-Forwarded-Host");
//        }
//
//        $acceptLanguage = "";
//
//        if($request->headers->has("Accept-Language")){
//            $acceptLanguage = $request->headers->get("Accept-Language");
//        }
//
//        $userAgent = "";
//
//        if($request->headers->has("user-agent")){
//            $userAgent = $request->headers->get("user-agent");
//        }
//
//        $referrer = "";
//
//        if($request->headers->has("referrer")){
//            $referrer = $request->headers->get("referrer");
//        }
//
//        $allHeaders = [];
//        foreach ($request->headers as $key=>$value){
//            $allHeaders[$key] = $value[0];
//        }
//
//
//
//        $dataToSend = [
//            "accessToken"=>$access_token,
//            "userId"=>null,
//            "clientId"=>null,
//            "referrer"=>$referrer,
//            "ipAddress"=>$ipAddress,
//            "host"=>$host,
//            "acceptLanguage"=>$acceptLanguage,
//            "userAgent"=>$userAgent,
//            "requestURL"=>$request->getRequestUri(),
//            "success"=>false,
//            "requestedScopes"=>"",
//            "requestedRoles"=>"",
//            "createdTime"=>date_create('now')->format('Y-m-d\TH:i:sO'),
//            "requestInfo"=>$allHeaders
//        ];
//
//
//
//
//        $roles = $request->route()->getAction("roles");
//
//        if($roles!=null){
//            $dataToSend["requestedRoles"] =  implode(",",$roles);
//        }
//
//        $scopes = $request->route()->getAction("scopes");
//
//        if($scopes!=null){
//            $dataToSend["requestedScopes"] =  implode(" ",$scopes);
//        }
//
//
//        $client = $this->getHttpClient();
//
//        $result = $client->post($this->getTokenInfoUrl(),[
//            "json"=>$dataToSend,
//            "headers"=>[
//                "Content-Type" => "application/json",
//                "access_token"=>$access_token
//            ]
//        ]);
//
//        if($result->getStatusCode() == 200) {
//            $token_check_response = json_decode($result->getBody()->getContents());
//
//
//            return [
//                "data"=>$token_check_response,
//                "status_code"=>200
//            ];
//
//        }
//
//        return [
//            "error"=>"Access denied for this resource",
//            "status_code"=>401
//        ];
//
//    }

}
