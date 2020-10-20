<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cidaas\OAuth2\Client\Provider\Cidaas;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

class IntrospectTokenOld extends TestCase
{
    // TODO rewrite to get real tokens for tests, instead of static tokens used here
    public function dontTestStuff()
    {
        Dotenv::createImmutable(__DIR__, 'testconfig.env')->load();

        $provider = new Cidaas([
            'base_url' => $_ENV['CIDAAS_BASE_URL'],
            'client_id' => $_ENV['CIDAAS_CLIENT_ID'],
            'client_secret' => $_ENV['CIDAAS_CLIENT_SECRET']
        ]);

        echo "Validate with Bearer";
        $tokenInfo = $provider->introspectToken([
            "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
        ], "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M");

        echo json_encode($tokenInfo);

        echo "Validate with Basic";
        $tokenInfo = $provider->introspectToken([
            "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
        ]);

        echo json_encode($tokenInfo);

        echo "Validate with scopes";
        $tokenInfo = $provider->introspectToken([
            "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
            "scopes" => ["email"],
        ]);

        echo json_encode($tokenInfo);

        echo "Validate with roles";
        $tokenInfo = $provider->introspectToken([
            "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
            "roles" => ["admin"],
        ]);

        echo json_encode($tokenInfo);

        echo "Validate with scopes and roles";
        $tokenInfo = $provider->introspectToken([
            "token" => "eyJhbGciOiJSUzI1NiIsImtpZCI6ImM1ZTIzZmViLTQyODQtNDMyZi1hZWIzLWRlMzJhNWFjMTZkNiJ9.eyJzaWQiOiIxMzczMmJkOC0wMWFlLTQyNmQtODY3MC01YTcwMzU1OTBlMmQiLCJzdWIiOiJBTk9OWU1PVVMiLCJhdWQiOiIzZTRhZDM0ZS05N2M1LTQxMGQtODJjOS0xZDlhNzE4MjBhODciLCJpYXQiOjE1NDA4MzIxNjQsImF1dGhfdGltZSI6MTU0MDgzMjE2NCwiaXNzIjoiaHR0cHM6Ly9uaWdodGx5YnVpbGQuY2lkYWFzLmRlIiwianRpIjoiNzA0MjI0ZTQtN2EwMy00YWZlLTgwYmUtYTVhNTE5ZWM0NzljIiwic2NvcGVzIjpbIm9wZW5pZCIsImVtYWlsIiwicHJvZmlsZSIsIm9mZmxpbmVfYWNjZXNzIiwicGhvbmUiXSwiZXhwIjoxNTQwOTE4NTY0fQ.Gam9PYjXJSQDEQ-tUZnMbjoaaIFX-i67wF1wZa6eJhixRZB-8pRxesQs6dHtOpv2dTKjbIMEzVuJvYF7mdi78C2Qu1ZtxWARGu54MLctpLY5Jzuuup55pzK7jD50mrNIBPK1yMygv1bkzxejTo_SiDzbkN8QTe2gloAce3Icf6M",
            "roles" => ["admin"],
            "scopes" => ["email"],
        ]);

        echo json_encode($tokenInfo);
    }
}
