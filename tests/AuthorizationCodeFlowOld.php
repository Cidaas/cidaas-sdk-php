<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use Dotenv\Dotenv;

// TODO rewrite to real phpunit TestCase, does not work with fgets-call in line 30

Dotenv::createImmutable(__DIR__, 'testconfig.env')->load();

$provider = new Cidaas([
    'base_url' => $_ENV['CIDAAS_BASE_URL'],
    'client_id' => $_ENV['CIDAAS_CLIENT_ID'],
    'client_secret' => $_ENV['CIDAAS_CLIENT_SECRET'],
    'redirect_uri' => 'http://localhost:8000'
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
