<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AbstractCidaasTestParent.php';

use Cidaas\OAuth2\Client\Provider\AbstractCidaasTestParent;
use GuzzleHttp\Psr7\Response;
use function PHPUnit\Framework\assertStringContainsString;

final class LoginWithBrowserTest extends AbstractCidaasTestParent {
    private static $LOGIN_URL = 'http://localhost:1111';

    protected function setUp(): void {
        $this->setUpCidaas(false, false);
    }

    public function test_loginWithBrowser_withRequestId_redirectsToLoginPage() {
        $this->mock->reset();
        $this->mock->append(new Response(302, ['location' => self::$LOGIN_URL]));

        $this->provider->loginWithBrowser();

        assertStringContainsString('Location: https://nightlybuild.cidaas.de/authz-srv/authz?client_id=' . $_ENV['CIDAAS_CLIENT_ID'] . '&response_type=code&scope=' . urlencode('openid profile offline_access') . '&redirect_uri=' . $_ENV['CIDAAS_REDIRECT_URI'] . '&nonce=', parent::$headers[0]);
    }
}