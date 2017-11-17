# Cidaas Provider for OAuth 2.0 Client


This package provides Cidaas OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require "cidaas/oauth2-cidaas:dev-master"
```

## Usage

Usage is the same as The League's OAuth client, using `Cidaas\OAuth2\Client\Provider\Cidaas` as the provider.


### Implicit Flow

```php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use \League\OAuth2\Client\Token\AccessToken;



$provider = new Cidaas([
    'baseUrl'                 => 'yourcidaasbaseurl',
    'clientId'                => 'xxxx',    // The client ID assigned to you by the provider
    'clientSecret'            => 'yyyy',   // The client password assigned to you by the provider
    'redirectUri'             => 'https://yourredirecturl'
]);


print_r($provider->getAuthorizationUrl(["response_type"=>'token']));
print_r("\n");


echo "Copy Paste the above URL in the browser and login and Enter the Access Token : ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);


$accessToken2 = new AccessToken(["access_token" => trim($line)]);
$resourceOwner = $provider->getResourceOwner($accessToken2);

print_r($resourceOwner);

```



### Authorization Code Flow

```php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;




$provider = new Cidaas([
    'baseUrl'                 => 'yourcidaasbaseurl',
    'clientId'                => 'xxxx',    // The client ID assigned to you by the provider
    'clientSecret'            => 'yyyy',   // The client password assigned to you by the provider
    'redirectUri'             => 'https://yourredirecturl'
]);


print_r($provider->getAuthorizationUrl(["response_type"=>'code']));
print_r("\n");

echo "Copy Paste the above URL in the browser and login and Enter the Code : ";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);

$accessToken = $provider->getAccessToken('authorization_code', [
    'code' => trim($line)
]);

print_r($accessToken->getToken());
print_r("\n");
print_r($accessToken->getRefreshToken());
print_r("\n");

$resourceOwner = $provider->getResourceOwner($accessToken);

print_r($resourceOwner);
print_r("\n");


```

### Refreshing a Token

```php

$refrehToken = $provider->getAccessToken('refresh_token', [
    'refresh_token' => trim($accessToken->getRefreshToken())
]);

print_r($refrehToken->getToken());
print_r("\n");

```

### Client Credentials Flow

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use \League\OAuth2\Client\Token\AccessToken;




$provider = new Cidaas([
    'baseUrl'                 => 'yourcidaasbaseurl',
    'clientId'                => 'xxxx',    // The client ID assigned to you by the provider
    'clientSecret'            => 'yyyy',   // The client password assigned to you by the provider
]);



$accessToken = $provider->getAccessToken('client_credentials');

print_r($accessToken->getToken());
print_r("\n");


$accessToken2 = new AccessToken(["access_token" => $accessToken->getToken()]);
$resourceOwner = $provider->getResourceOwner($accessToken2);

print_r($resourceOwner);
print_r("\n");
```

## Validate Access , Roles, Scopes. 

#### Parse request and get the details 


To validate the Access by the Token, we need to pass the following information to the `validateAccessByToken` 

```
$provider->validateAccessByToken($requestData,$rolesArray,$scopesArray)
```

##### 1. RequestData (Used for Authentication)

The request data is sending the page/api request meta data to SDK. 

```php
$requestData = [
    "access_token"=>"access_token_from_request",
    "requestURL"=>"Target URL from the request",
    "headers"=>"headers from the request",
];

```

##### 2. RolesArray (Used for Authorization)

To Authorize the access for the token, ie, some page is allowed to view by certain users with certain roles. in that case we just need to pass that required roles in this. 

For example :  page /employees allowed to view only by user who having the Role "HR"

##### 2. ScopesArray (Used for Authorization)

To Authorize the access for the token, ie, some page is allowed to view by certain scopes. in that case we just need to pass that required scopes in this. 

For example :  page /leavelist allowed to view only by access token that conatains scope leave:read


   

#### Example snippet for Laravel Framwork

Add this in your web framework side.


```php
public function extractHeaderInfo(Request $request)
    {

        $responseData = [
            "requestURL" => $request->getRequestUri()
        ];
        $access_token_key = "access_token";

        $access_token = null;

        if ($request->headers->has($access_token_key)) {
            $access_token = $request->headers->get($access_token_key);
        }

        if ($access_token == null && $request->query($access_token_key) != null) {
            $access_token = $request->query($access_token_key);
        }

        if ($access_token == null && $request->headers->has("authorization")) {
            $auth = $request->headers->get("authorization");

            if (strtolower(substr($auth, 0, strlen("bearer"))) === "bearer") {
                $authvals = explode(" ", $auth);

                if (sizeof($authvals) > 1) {
                    $access_token = $authvals[1];
                }
            }
        }

        if ($access_token == null && $request->cookies->get("access_token")) {
            $access_token = $request->cookies->get("access_token");

        }

        $responseData["access_token"]  = $access_token;


        $responseData["headers"] = [];
        foreach ($request->headers as $key => $value) {

            $responseData["headers"][$key] = $value[0];
        }

        return $responseData;
    }
```

#### Use SDK function

```php

$parsedData = $this->extractHeaderInfo($request);

$provider = new Cidaas([
    'baseUrl'                 => 'yourcidaasbaseurl',
    'clientId'                => 'xxxx',    // The client ID assigned to you by the provider
    'clientSecret'            => 'yyyy',   // The client password assigned to you by the provider
    'redirectUri'             => 'https://yourdomain/user-ui/html/welcome.html'
]);

$response = $provider->validateToken($parsedData,["ADMIN","MANAGER"],["products:read","products:write"]);

if($response->status_code == 200){
    $userInfo = $response->data;
    $roles = $userInfo->roles;
    $scopes = $userInfo->scopes;
    $userId = $userInfo->userId;
    
    print_r("Valid access token");
    
    // Your Code here
    
    
}else{
    print_r("Invalid access token");
}
print_r("\n");
```




### Validate if the Token Expired or not 


```php
$isExpired = $provider->isTokenExpired($accessToken->getToken());
if($isExpired){
    print_r("Invalid access token");
}else{
    print_r("Valid access token");
}
print_r("\n");
```




