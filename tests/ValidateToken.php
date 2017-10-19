<?php
/**
 * Created by PhpStorm.
 * User: vimalprakash
 * Date: 19/10/17
 * Time: 4:58 PM
 */


require_once __DIR__ . '/../vendor/autoload.php';


use Cidaas\OAuth2\Client\Provider\Cidaas;




$provider = new Cidaas([
    'baseUrl'                 => 'yourcidaasdomain'
]);

$parsedInfo = [
    "access_token"=> "accesstoken from cidaas",
    "headers"=>[
        "x-forwarded-for"=>"192.168.2.1",
        "user-agent"=>"Chrome"
    ],
    "requestURL"=>"/test"

];
$result = $provider->validateToken($parsedInfo,null,null);

print_r($result);
print_r("\n");

$tokenExpired = $provider->isTokenExpired($parsedInfo["access_token"]);

if($tokenExpired == true){
    print_r("In valid token");
    print_r("\n");
}else{
    print_r("Valid token");
    print_r("\n");
}


