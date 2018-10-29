<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;

$provider = new Cidaas([
    'base_url' => 'https://nightlybuild.cidaas.de',
    'client_id' => '44afd65d-ce02-45d1-93d8-b77b2bd286d2', // The client ID assigned to you by the provider
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
