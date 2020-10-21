<?php

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Cidaas\OAuth2\Client\Provider\Cidaas;
use Dotenv\Dotenv;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

abstract class AbstractCidaasTestParent extends TestCase {
    protected static $REQUEST_ID = '758d173b-89fb-4d37-84b4-e52a0ce63a78';
    protected static $CODE = '8c306345-f67f-4535-8388-e609df78a190';
    protected static $SUB = 'df838cf7-dc95-44de-a1cc-5c0eafb0f6db';
    protected static $ACCESS_TOKEN = 'eyJhbGciOiJSUzI1NiIsImtpZCI6IjA2MTVmNzk5LWYzZmMtNDM0ZS04NDEyLWUxYWJjMzdhZWZlOSJ9.eyJhbXIiOlsiMiJdLCJ1YV9oYXNoIjoiZDViNWI4NDJiOGUxOTM4YzIzNDJmMGY5ZTdjMzI0ZWEiLCJzaWQiOiIwOWJkMDAyNC02MjlmLTRmZGItODZmMi0zY2NmOTMyNzc4MTIiLCJzdWIiOiJkZjgzOGNmNy1kYzk1LTQ0ZGUtYTFjYy01YzBlYWZiMGY2ZGIiLCJpc3ViIjoiM2Y5NTU1ZjktYWMwMy00ZDMzLTljZGItNDMyZTFiY2UxNDBmIiwiYXVkIjoiN2Q1YWJkYmMtODE4Ni00YzgyLTk5NDMtZjcxNTA2YWFmNzAwIiwiaWF0IjoxNjAzMTA2MTM1LCJhdXRoX3RpbWUiOjE2MDMxMDYxMzUsImlzcyI6Imh0dHA6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiMzBmZGZlOTYtMzE4Ni00YWQ5LWEwZDUtMjY0ZTc4NTYxMzg3Iiwibm9uY2UiOiIxNjAzMTA2MTEyIiwic2NvcGVzIjpbIm9wZW5pZCIsImlkZW50aXRpZXMiXSwicm9sZXMiOlsiVVNFUiJdLCJncm91cHMiOlt7Imdyb3VwSWQiOiJDSURBQVNfQURNSU5TIiwicm9sZXMiOlsiU0VDT05EQVJZX0FETUlOIl19XSwiZXhwIjoxNjAzMTkyNTM1fQ.DlkhMWZpXvTMQo3m0xhaQERDqVSZzJqd0itmfaV1DgXxhlncHE3c-iA6SEXm5yMemBkjEtxm6P6mF5qhIeGG43vobz5jY6ARSpNNZLMmeS1zVHdZYiF3Vn1dSzm-J7efU_-NVWX6d_5tEZygzxt7meZdbIbVo-2cF1U3rwxJjZg';

    protected static function loginSuccessfulResponse() {
        return '{"success":true,"status":200,"data":{"code":"' . self::$CODE . '","viewtype":"login","grant_type":"login"}}';
    }

    protected static function getAccessTokenSuccessfulResponse() {
        return '{"token_type":"Bearer","sub":"' . self::$SUB . '","expires_in":86400,"id_token_expires_in":86400,"access_token":"' . self::$ACCESS_TOKEN . '","id_token":"eyJhbGciOiJSUzI1NiIsImtpZCI6IjA2MTVmNzk5LWYzZmMtNDM0ZS04NDEyLWUxYWJjMzdhZWZlOSJ9.eyJhbXIiOlsiMiJdLCJ1YV9oYXNoIjoiZDViNWI4NDJiOGUxOTM4YzIzNDJmMGY5ZTdjMzI0ZWEiLCJzaWQiOiI1NjBlYTM5Ni1lYTBjLTQ2ZTEtYThhZS02MjFiMzIzMmI4NzMiLCJzdWIiOiJkZjgzOGNmNy1kYzk1LTQ0ZGUtYTFjYy01YzBlYWZiMGY2ZGIiLCJpc3ViIjoiM2Y5NTU1ZjktYWMwMy00ZDMzLTljZGItNDMyZTFiY2UxNDBmIiwiYXVkIjoiN2Q1YWJkYmMtODE4Ni00YzgyLTk5NDMtZjcxNTA2YWFmNzAwIiwiaWF0IjoxNjAzMTM1NzIzLCJhdXRoX3RpbWUiOjE2MDMxMzU3MjMsImlzcyI6Imh0dHBzOi8vbmlnaHRseWJ1aWxkLmNpZGFhcy5kZSIsImp0aSI6ImEwMzc1MGNkLTQ4MzAtNGQ3MS1hOTNjLTkxNzQwNjA2M2Y0OCIsIm5vbmNlIjoiMTYwMzEzNTcwMSIsInNjb3BlcyI6WyJvcGVuaWQiLCJpZGVudGl0aWVzIl0sInJvbGVzIjpbIlVTRVIiXSwiZ3JvdXBzIjpbeyJncm91cElkIjoiQ0lEQUFTX0FETUlOUyIsInJvbGVzIjpbIlNFQ09OREFSWV9BRE1JTiJdfV0sImV4cCI6MTYwMzIyMjEyNCwiYXRfaGFzaCI6ImF0MlBVYTRRY2M5Vm83N0xGY1RtYXciLCJjX2hhc2giOiJZcUs5OVVxTFRtMHhlS3VMWTA2TjRRIiwiaWRlbnRpdGllcyI6W3sicHJvdmlkZXIiOiJzZWxmIiwiaWRlbnRpdHlJZCI6IjNmOTU1NWY5LWFjMDMtNGQzMy05Y2RiLTQzMmUxYmNlMTQwZiIsImVtYWlsIjoiam9lcmcua25vYmxvY2hAd2lkYXMuZGUiLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZSwibW9iaWxlX251bWJlciI6IiIsIm1vYmlsZV9udW1iZXJfdmVyaWZpZWQiOmZhbHNlfV0sImVtYWlsIjoiam9lcmcua25vYmxvY2hAd2lkYXMuZGUifQ.QZR7TOWf75Ucrwi6ehQqhANR1Bq3mk9fubaJBeRte-XLRgfJ41vrx7whu2geO_ZfFnPbeWO-j1TZQOVusxRMc_HJ_Yw_8afUrYMufwnh4nMCP85lKV3L5zwmkWZtVX46t6GIRE4fJOKawZbEOt-4DxZZTTr-60gslD1HohxBby0","identity_id":"3f9555f9-ac03-4d33-9cdb-432e1bce140f"}';
    }

    protected $provider;
    protected $mock;

    /**
     * Common setup method for Cidaas-Tests.
     * @param bool $integrationTest {@code true} for integration testing, {@code false} for unit testing
     */
    protected function setUpCidaas($integrationTest = false): void {
        Dotenv::createImmutable(__DIR__, 'testconfig.env')->load();

        $this->mock = new MockHandler([
            new Response(200, [], '{"issuer":"https://nightlybuild.cidaas.de","userinfo_endpoint":"https://nightlybuild.cidaas.de/users-srv/userinfo","authorization_endpoint":"https://nightlybuild.cidaas.de/authz-srv/authz","introspection_endpoint":"https://nightlybuild.cidaas.de/token-srv/introspect","introspection_async_update_endpoint":"https://nightlybuild.cidaas.de/token-srv/introspect/async/tokenusage","revocation_endpoint":"https://nightlybuild.cidaas.de/token-srv/revoke","token_endpoint":"https://nightlybuild.cidaas.de/token-srv/token","jwks_uri":"https://nightlybuild.cidaas.de/.well-known/jwks.json","check_session_iframe":"https://nightlybuild.cidaas.de/session/check_session","end_session_endpoint":"https://nightlybuild.cidaas.de/session/end_session","social_provider_token_resolver_endpoint":"https://nightlybuild.cidaas.de/login-srv/social/token","device_authorization_endpoint":"https://nightlybuild.cidaas.de/authz-srv/device/authz","subject_types_supported":["public"],"scopes_supported":["openid","profile","email","phone","address","offline_access","identities","roles","groups"],"response_types_supported":["code","token","id_token","code token","code id_token","token id_token","code token id_token"],"response_modes_supported":["query","fragment","form_post"],"grant_types_supported":["implicit","authorization_code","refresh_token","password","client_credentials"],"id_token_signing_alg_values_supported":["HS256","RS256"],"id_token_encryption_alg_values_supported":["RS256"],"id_token_encryption_enc_values_supported":["A128CBC-HS256"],"userinfo_signing_alg_values_supported":["HS256","RS256"],"userinfo_encryption_alg_values_supported":["RS256"],"userinfo_encryption_enc_values_supported":["A128CBC-HS256"],"request_object_signing_alg_values_supported":["HS256","RS256"],"request_object_encryption_alg_values_supported":["RS256"],"request_object_encryption_enc_values_supported":["A128CBC-HS256"],"token_endpoint_auth_methods_supported":["client_secret_basic","client_secret_post","client_secret_jwt","private_key_jwt"],"token_endpoint_auth_signing_alg_values_supported":["HS256","RS256"],"claims_supported":["aud","auth_time","created_at","email","email_verified","exp","family_name","given_name","iat","identities","iss","mobile_number","name","nickname","phone_number","picture","sub"],"claims_parameter_supported":false,"claim_types_supported":["normal"],"service_documentation":"https://docs.cidaas.de/","claims_locales_supported":["en-US"],"ui_locales_supported":["en-US","de-DE"],"display_values_supported":["page","popup"],"code_challenge_methods_supported":["plain","S256"],"request_parameter_supported":true,"request_uri_parameter_supported":true,"require_request_uri_registration":false,"op_policy_uri":"https://www.cidaas.com/privacy-policy/","op_tos_uri":"https://www.cidaas.com/terms-of-use/","scim_endpoint":"https://nightlybuild.cidaas.de/users-srv/scim/v2"}'),
            new Response(200, [], '{"success": true, "status": 200, "data": {"groupname": "default", "lang": "", "view_type": "login", "requestId": "' . self::$REQUEST_ID . '"}}')]);

        if ($integrationTest) {
            echo "Integration testing active. Please make sure credentials at integration-testconfig.env are correct and proxy is running at " . $_ENV['CIDAAS_BASE_URL_WITH_PROXY'];
            Dotenv::createMutable(__DIR__, 'integration-testconfig.env')->load();

            $this->provider = new Cidaas([
                'base_url' => $_ENV['CIDAAS_BASE_URL_WITH_PROXY'],
                'client_id' => $_ENV['CIDAAS_CLIENT_ID'],
                'client_secret' => $_ENV['CIDAAS_CLIENT_SECRET'],
                'redirect_uri' => $_ENV['CIDAAS_REDIRECT_URI'],
                'debug' => true
            ]);
        } else {
            $this->provider = new Cidaas([
                'base_url' => $_ENV['CIDAAS_BASE_URL'],
                'client_id' => $_ENV['CIDAAS_CLIENT_ID'],
                'client_secret' => $_ENV['CIDAAS_CLIENT_SECRET'],
                'redirect_uri' => $_ENV['CIDAAS_REDIRECT_URI'],
                'handler' => HandlerStack::create($this->mock),
                'debug' => true
            ]);
        }
    }
}