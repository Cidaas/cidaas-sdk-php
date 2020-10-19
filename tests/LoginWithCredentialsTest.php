<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use Dotenv\Dotenv;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

final class LoginWithCredentialsTest extends TestCase
{
    private static $USERNAME = 'user@widas.de';
    private static $USERNAME_TYPE = 'email';
    private static $PASSWORD = 'password';
    private static $SUB = '603f0056-6b51-443c-ac82-3e2430522df1';
    private static $REQUEST_ID = '758d173b-89fb-4d37-84b4-e52a0ce63a78';
    private static $loginUnsuccessfulWithUnkownUserResponse = '{"success":false,"status":417,"error":{"error":"invalid_username_password","username":"user@widas.de","username_type":"email","error_description":"Given username or password is invalid","view_type":"login","requestId":"bb3b6908-64e4-4ccf-891e-908fc9dd1236","suggested_url":"https://nightlybuild.cidaas.de/user-ui/login?groupname=default&lang=&view_type=login&error=invalid_username_password&username=user%40widas.de&username_type=email&error_description=Given%20username%20or%20password%20is%20invalid&requestId=bb3b6908-64e4-4ccf-891e-908fc9dd1236"}}';
    private static $code = '8c306345-f67f-4535-8388-e609df78a190';

    private static function loginSuccessfulResponse()
    {
        return '{"success":true,"status":200,"data":{"code":"' . self::$code . '","viewtype":"login","grant_type":"login"}}';
    }

    private static function loginUnsuccessfulWithKnownUserResponse()
    {
        return '{"success":false,"status":417,"error":{"error":"invalid_username_password","username":"user@widas.de","username_type":"email","view_type":"login","sub":"' . self::$SUB . '","q":"dddb62b2-7e2a-4143-a492-d5818802cdac","requestId":"cfc1d841-4dab-4ffa-ab6f-fc2662a9b206","suggested_url":"https://nightlybuild.cidaas.de/user-ui/login?groupname=default&lang=&view_type=login&error=invalid_username_password&username=joerg.knobloch%40widas.de&username_type=email&sub=dddb62b2-7e2a-4143-a492-d5818802cdac&q=dddb62b2-7e2a-4143-a492-d5818802cdac&requestId=cfc1d841-4dab-4ffa-ab6f-fc2662a9b206"}}';
    }

    private $provider;
    private $mock;
    private $responsePromise;

    protected function setUp(): void
    {
        Dotenv::createImmutable(__DIR__, 'testconfig.env')->load();

        $this->mock = new MockHandler([
            new Response(200, [], '{"issuer":"https://nightlybuild.cidaas.de","userinfo_endpoint":"https://nightlybuild.cidaas.de/users-srv/userinfo","authorization_endpoint":"https://nightlybuild.cidaas.de/authz-srv/authz","introspection_endpoint":"https://nightlybuild.cidaas.de/token-srv/introspect","introspection_async_update_endpoint":"https://nightlybuild.cidaas.de/token-srv/introspect/async/tokenusage","revocation_endpoint":"https://nightlybuild.cidaas.de/token-srv/revoke","token_endpoint":"https://nightlybuild.cidaas.de/token-srv/token","jwks_uri":"https://nightlybuild.cidaas.de/.well-known/jwks.json","check_session_iframe":"https://nightlybuild.cidaas.de/session/check_session","end_session_endpoint":"https://nightlybuild.cidaas.de/session/end_session","social_provider_token_resolver_endpoint":"https://nightlybuild.cidaas.de/login-srv/social/token","device_authorization_endpoint":"https://nightlybuild.cidaas.de/authz-srv/device/authz","subject_types_supported":["public"],"scopes_supported":["openid","profile","email","phone","address","offline_access","identities","roles","groups"],"response_types_supported":["code","token","id_token","code token","code id_token","token id_token","code token id_token"],"response_modes_supported":["query","fragment","form_post"],"grant_types_supported":["implicit","authorization_code","refresh_token","password","client_credentials"],"id_token_signing_alg_values_supported":["HS256","RS256"],"id_token_encryption_alg_values_supported":["RS256"],"id_token_encryption_enc_values_supported":["A128CBC-HS256"],"userinfo_signing_alg_values_supported":["HS256","RS256"],"userinfo_encryption_alg_values_supported":["RS256"],"userinfo_encryption_enc_values_supported":["A128CBC-HS256"],"request_object_signing_alg_values_supported":["HS256","RS256"],"request_object_encryption_alg_values_supported":["RS256"],"request_object_encryption_enc_values_supported":["A128CBC-HS256"],"token_endpoint_auth_methods_supported":["client_secret_basic","client_secret_post","client_secret_jwt","private_key_jwt"],"token_endpoint_auth_signing_alg_values_supported":["HS256","RS256"],"claims_supported":["aud","auth_time","created_at","email","email_verified","exp","family_name","given_name","iat","identities","iss","mobile_number","name","nickname","phone_number","picture","sub"],"claims_parameter_supported":false,"claim_types_supported":["normal"],"service_documentation":"https://docs.cidaas.de/","claims_locales_supported":["en-US"],"ui_locales_supported":["en-US","de-DE"],"display_values_supported":["page","popup"],"code_challenge_methods_supported":["plain","S256"],"request_parameter_supported":true,"request_uri_parameter_supported":true,"require_request_uri_registration":false,"op_policy_uri":"https://www.cidaas.com/privacy-policy/","op_tos_uri":"https://www.cidaas.com/terms-of-use/","scim_endpoint":"https://nightlybuild.cidaas.de/users-srv/scim/v2"}'),
            new Response(200, [], '{"success": true, "status": 200, "data": {"groupname": "default", "lang": "", "view_type": "login", "requestId": "' . self::$REQUEST_ID . '"}}')]);

        $this->provider = new Cidaas([
            'base_url' => $_ENV['CIDAAS_BASE_URL'],
            //'base_url' => $_ENV['CIDAAS_BASE_URL_WITH_PROXY'],
            'client_id' => $_ENV['CIDAAS_CLIENT_ID'],
            'client_secret' => $_ENV['CIDAAS_CLIENT_SECRET'],
            'redirect_uri' => $_ENV['CIDAAS_REDIRECT_URI'],
            'handler' => HandlerStack::create($this->mock),
            'debug' => true
        ]);

        $this->responsePromise = $this->provider->getRequestId();
    }

    public function test_loginWithCredentials_withValidCredentialsGiven_serverCalledWithClientIdAndSecret()
    {
        $this->mock->append(new Response(200, [], self::loginSuccessfulResponse()));

        $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials(self::$USERNAME, self::$USERNAME_TYPE, self::$PASSWORD, $requestId);
        })->wait();

        $request = $this->mock->getLastRequest();
        $body = json_decode($request->getBody(), true);

        assertEquals(self::$USERNAME, $body['username']);
        assertEquals(self::$PASSWORD, $body['password']);
        assertEquals(self::$USERNAME_TYPE, $body['username_type']);
        assertEquals(self::$REQUEST_ID, $body['requestId']);
    }

    public function test_loginWithCredentials_withValidCredentialsGiven_returnsLoginSuccessfulFromServer()
    {
        $this->mock->append(new Response(200, [], self::loginSuccessfulResponse()));

        $promise = $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials(self::$USERNAME, self::$USERNAME_TYPE, self::$PASSWORD, $requestId);
        });

        $response = $promise->wait();
        assertTrue($response['success']);
        assertEquals(200, $response['status']);
        assertEquals(self::$code, $response['data']['code']);
    }

    public function test_loginWithCredentials_withInvalidCredentialsAndKnownUsernameGiven_returnsLoginUnsuccessfulFromServer()
    {
        $this->mock->append(new Response(417, [], self::loginUnsuccessfulWithKnownUserResponse()));

        $promise = $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials(self::$USERNAME, self::$USERNAME_TYPE, self::$PASSWORD, $requestId);
        });

        try {
            $promise->wait();
            self::fail('Promise should return exception');
        } catch (ClientException $exception) {
            assertEquals(417, $exception->getCode());
            $response = json_decode($exception->getResponse()->getBody(), true);
            assertFalse($response['success']);
            assertEquals(417, $response['status']);
            assertNull($response['data']);
            assertEquals(self::$SUB, $response['error']['sub']);
            assertEquals('invalid_username_password', $response['error']['error']);
        }
    }

    public function test_loginWithCredentials_withInvalidCredentialsAndUnknownUsernameGiven_returnsLoginUnsuccessfulFromServer()
    {
        $this->mock->append(new Response(417, [], self::$loginUnsuccessfulWithUnkownUserResponse));

        $promise = $this->responsePromise->then(function ($requestId) {
            return $this->provider->loginWithCredentials(self::$USERNAME, self::$USERNAME_TYPE, self::$PASSWORD, $requestId);
        });

        try {
            $promise->wait();
            self::fail('Promise should return exception');
        } catch (ClientException $exception) {
            assertEquals(417, $exception->getCode());
            $response = json_decode($exception->getResponse()->getBody(), true);
            assertFalse($response['success']);
            assertEquals(417, $response['status']);
            assertNull($response['data']);
            assertEquals('invalid_username_password', $response['error']['error']);
            assertNull($response['error']['login']);
        }
    }
}
