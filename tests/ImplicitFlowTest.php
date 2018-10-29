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
