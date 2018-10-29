# Cidaas Provider for Openid Connect and OAuth 2.0 Client


## Installation

To install, use composer:

```
composer require "cidaas/oauth2-cidaas:dev-cidaas-v2"
```

## Usage


### Implicit Flow

```php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;

$provider = new Cidaas([
    'base_url' => 'https://cidaas-base-url.cidaas.de',
    'client_id' => '55afd65d-ce02-45d1-93d8-b77b2bd286d2', // The client ID assigned to you by the provider
    'client_secret' => '7ea886b9-2711-447c-baba-c5572ad7e1ac', // The client password assigned to you by the provider
    'redirect_uri' => 'http://localhost:8080',
]);

$authz_url = $provider->getAuthorizationUrl(
    [
        "scope" => "openid email profile",
        "response_type" => 'token',
    ]
);

echo $authz_url;
print_r("\n");

echo "Copy Paste the above URL in the browser and login and Enter the Code : ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);

$resourceOwner = $provider->getUserInfo(trim($line));

print_r("\n");
echo "User info";
print_r("\n");
echo json_encode($resourceOwner);


```



### Authorization Code Flow

```php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;

$provider = new Cidaas([
    'base_url' => 'https://cidaas-base-url.cidaas.de',
    'client_id' => '55afd65d-ce02-45d1-93d8-b77b2bd286d2', // The client ID assigned to you by the provider
    'client_secret' => '7ea886b9-2711-447c-baba-c5572ad7e1ac', // The client password assigned to you by the provider
    'redirect_uri' => 'http://localhost:8080',
]);

$authz_url = $provider->getAuthorizationUrl(
    [
        "scope" => "openid email profile offline_access",
    ]
);

echo $authz_url;
print_r("\n");

echo "Copy Paste the above URL in the browser and login and Enter the Code : ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);

$access_token = $provider->getAccessToken('authorization_code', [
    'code' => trim($line),
]);

echo "Access Token";
print_r("\n");
echo $access_token["access_token"];

print_r("\n");
echo "Refresh Token";
print_r("\n");
echo $access_token["refresh_token"];

$resourceOwner = $provider->getUserInfo($access_token["access_token"]);

print_r("\n");
echo "User info";
print_r("\n");
echo json_encode($resourceOwner);

$refresh_token = $provider->getAccessToken('refresh_token', [
    'refresh_token' => trim($access_token["refresh_token"]),
]);

print_r("\n");
echo "Token From Access Token";
print_r("\n");
echo $refresh_token["access_token"];
print_r("\n");



```

### Refreshing a Token

```php

$refresh_token = $provider->getAccessToken('refresh_token', [
    'refresh_token' => trim($access_token["refresh_token"]),
]);

print_r("\n");
echo "Token From Access Token";
print_r("\n");
echo $refresh_token["access_token"];
print_r("\n");

```

### Client Credentials Flow

```php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;

$provider = new Cidaas([
    'base_url' => 'https://cidaas-base-url.cidaas.de',
    'client_id' => '3e4ad34e-97c5-410d-82c9-1d9a71820a87', // The client ID assigned to you by the provider
    'client_secret' => 'cf914b42-6a0e-48a1-aea6-935bfa749027', // The client password assigned to you by the provider
]);

$access_token = $provider->getAccessToken('client_credentials', [

]);

echo "Access Token";
print_r("\n");
echo $access_token["access_token"];

$resourceOwner = $provider->getUserInfo($access_token["access_token"], "c568bec6-15ff-4278-a165-415fab9a622a");

print_r("\n");
echo "User info";
print_r("\n");
echo json_encode($resourceOwner);

```

## Validate Access , Roles, Scopes. 

```php

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;

$provider = new Cidaas([
    'base_url' => 'https://cidaas-base-url.cidaas.de',
    'client_id' => '3e4ad34e-97c5-410d-82c9-1d9a71820a87', // The client ID assigned to you by the provider
    'client_secret' => 'cf914b42-6a0e-48a1-aea6-935bfa749027', // The client password assigned to you by the provider
]);

echo "Validate with Bearer";
$tokenInfo = $provider->introspectToken([
    "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
], "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M");

echo json_encode($tokenInfo);

echo "Validate with Basic";
$tokenInfo = $provider->introspectToken([
    "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
]);

echo json_encode($tokenInfo);

echo "Validate with scopes";
$tokenInfo = $provider->introspectToken([
    "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
    "scopes" => ["email"],
]);

echo json_encode($tokenInfo);

echo "Validate with roles";
$tokenInfo = $provider->introspectToken([
    "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
    "roles" => ["admin"],
]);

echo json_encode($tokenInfo);

echo "Validate with scopes and roles";
$tokenInfo = $provider->introspectToken([
    "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
    "roles" => ["admin"],
    "scopes" => ["email"],
]);

echo json_encode($tokenInfo);


```

