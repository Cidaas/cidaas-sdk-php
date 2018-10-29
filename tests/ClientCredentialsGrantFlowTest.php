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
