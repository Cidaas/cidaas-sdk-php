<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

final class ClientCredentialsGrantFlowTest extends TestCase
{
    private $provider;
    private $accessToken;

    protected function setUp(): void
    {
        Dotenv::createImmutable(__DIR__, 'nightlybuild-config.env')->load();

        $this->provider = new Cidaas([
            'base_url' => $_ENV['CIDAAS_BASE_URL'],
            'client_id' => $_ENV['CIDAAS_CLIENT_ID'],
            'client_secret' => $_ENV['CIDAAS_CLIENT_SECRET']
        ]);
        $this->accessToken = $this->provider->getAccessToken('client_credentials', []);
    }


    public function testAccess()
    {
        $resourceOwner = $this->provider->getUserInfo($this->accessToken["access_token"], $_ENV['USER_ID']);

        $this->assertEquals('joerg.knobloch@widas.de', $resourceOwner['email']);
    }

    // TODO tests for denied access
}
