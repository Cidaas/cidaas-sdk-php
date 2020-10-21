<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AbstractCidaasTestParent.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use Cidaas\OAuth2\Client\Provider\GrantType;
use Dotenv\Dotenv;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertEquals;

final class GetAccessTokenTest extends AbstractCidaasTestParent {
    private static $getAccessTokenInvalidGrantResponse = '{"error":"invalid_grant","error_description":"The provided authorization grant is invalid : invalid code or expired or already used or revoked"}';

    private $responsePromise;

    protected function setUp(): void {
        $this->setUpCidaas();

        $this->mock->append(new Response(200, [], self::loginSuccessfulResponse()));

        $this->responsePromise = $this->provider->getRequestId()->then(function ($requestId) {
            return $this->provider->loginWithCredentials($_ENV['USERNAME'], $_ENV['USERNAME_TYPE'], $_ENV['PASSWORD'], $requestId);
        });
    }

    public function test_getAccessToken_withGrantTypeAuthorizationCode_serverCalledWithClientIdAndSecret() {
        $this->mock->append(new Response(200, [], self::getAccessTokenSuccessfulResponse()));

        $this->responsePromise->then(function ($credentialsResponse) {
            $code = $credentialsResponse['data']['code'];
            return $this->provider->getAccessToken(GrantType::AuthorizationCode, ['code' => $code]);
        })->wait();

        $request = $this->mock->getLastRequest();
        $body = $request->getBody();

        assertStringContainsString('client_id=' . urlencode($_ENV['CIDAAS_CLIENT_ID']), $body);
        assertStringContainsString('client_secret=' . urlencode($_ENV['CIDAAS_CLIENT_SECRET']), $body);
        assertStringContainsString('redirect_uri=' . urlencode($_ENV['CIDAAS_REDIRECT_URI']), $body);
        assertStringContainsString('grant_type=' . urlencode(GrantType::AuthorizationCode), $body);
        assertStringContainsString('code=' . urlencode(self::$CODE), $body);
    }

    public function test_getAccessToken_withGrantTypeAuthorizationCode_returnsAccessToken() {
        $this->mock->append(new Response(200, [], self::getAccessTokenSuccessfulResponse()));

        $promise = $this->responsePromise->then(function ($credentialsResponse) {
            $code = $credentialsResponse['data']['code'];
            return $this->provider->getAccessToken(GrantType::AuthorizationCode, ['code' => $code]);
        });

        $response = $promise->wait();

        assertEquals(self::$ACCESS_TOKEN, $response['access_token']);
    }

    public function test_loginWithCredentials_withInvalidCredentialsAndKnownUsernameGiven_returnsLoginUnsuccessfulFromServer() {
        $this->mock->append(new Response(400, [], self::$getAccessTokenInvalidGrantResponse));

        $promise = $this->responsePromise->then(function ($credentialsResponse) {
            $code = 'someOtherCode';
            return $this->provider->getAccessToken(GrantType::AuthorizationCode, ['code' => $code]);
        });

        try {
            $promise->wait();
            self::fail('Promise should return exception');
        } catch (ClientException $exception) {
            assertEquals(400, $exception->getCode());
            $response = json_decode($exception->getResponse()->getBody(), true);
            assertEquals('invalid_grant', $response['error']);
        }
    }
}
