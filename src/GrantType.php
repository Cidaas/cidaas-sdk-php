<?php

namespace Cidaas\OAuth2\Client\Provider;

interface GrantType
{
    const AuthorizationCode = 'authorization_code';
    const RefreshToken = 'refresh_token';
    const ClientCredentials = 'client_credentials';
}