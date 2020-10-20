<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use Dotenv\Dotenv;

// TODO rewrite to real phpunit TestCase, does not work with fgets-call in line 31

Dotenv::createImmutable(__DIR__, 'testconfig.env')->load();

$provider = new Cidaas([
    'base_url' => $_ENV['CIDAAS_BASE_URL'],
    'client_id' => $_ENV['CIDAAS_CLIENT_ID'],
    'client_secret' => $_ENV['CIDAAS_CLIENT_SECRET'],
    'redirect_uri' => 'http://localhost:8080'
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
