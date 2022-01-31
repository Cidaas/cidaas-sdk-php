# Cidaas SDK for php


## Installation

In order to use this sdk, you need to perform the following steps:

1. Add the cidaas repository to your composer.json configuration
   ```
   "repositories": [
     {
       "type": "vcs",
       "url": "https://github.com/Cidaas/cidaas-sdk-php.git"
     }
   ]
   ```
1. Install sdk dependency using composer
   ```
   composer require "cidaas/oauth2-cidaas:<tag / branch>", e.g. "cidaas/oauth2-cidaas:dev-master"
   ```


## Integration

### Prerequisites
Before you can start integrating this sdk, you need the following information:
- Base URL - URL of cidaas server
- Client id - issued by cidaas to identify your application
- Client secret - issued by cidaas to identify your application
- Redirect URI - URI to redirect to after successful login

In addition to this data, you should read the documentation at https://docs.cidaas.de with special attention to the integration chapters.

Please note that the web server running your php application needs direct access to the base url mentioned above. If this is not possible, think about
using the javascript sdk for browser side integration.

### Base communication provider
All communication is done using the php class `Cidaas\OAuth2\Client\Provider\Cidaas` (called *provider* in the following chapters). In order to be
able to use this provider, you need to instantiate it with the prerequisites data mentioned above.
```php
<?php
$cidaas = new Cidaas('https://yourcidaasinstance.cidaas.de', 'client id', 'client secret', 'https://yourwebsite/redirectAfterLogin');
?>
```

In addition to these required parameters, there are two optional constructor parameters:
- handler: to interfere with the http connections being performed
- debug: to enable debug mode

During construction, an http call is being performed to read base configuration data from the server.

### Integration of hosted login page
In order to integrate a hosted login page, you might just implement a simple button, which itself calls the login method when clicked.
```php
<?php
    $provider->loginWithBrowser();
?>
```
After successful login, the browser is redirected to your selected redirectUri (see Prerequisites). Using the method `loginCallback` enables you to retrieve the authorization code for retrieving access and refresh tokens.
```php
<?php
$parameters = $provider->loginCallback();
if (array_key_exists('code', $parameters)) {
    $loginResultCode = $parameters['code'];     
}
?>
```

### Building your own login page
You can build your own login page and use a direct login mechanism. In order to access the login method, you need to retrieve a requestId first.
```php
<?php
    try {
        $loginResult = $provider->getRequestId('openid identities profile offline_access')->then(function ($requestId) {
            return $provider->loginWithCredentials($username, 'email', $password, $requestId);
        })->wait();
        $loginResultCode = $loginResult['data']['code'];
    } catch (ClientException $exception) {
        $errorBody = json_decode($exception->getResponse()->getBody(), true);
        $passwordErrorMessage = $errorBody['error']['error_description'];
    }
?>
```

### Retrieve tokens after login
Using the login result code, you can then get your access token and refresh token using the method `getAccessToken` with GrantType `AuthorizationCode`. 
```php
<?php
    $accessTokenResponse = $provider->getAccessToken(GrantType::AuthorizationCode, $loginResultCode)->wait();
    $accessToken = $accessTokenResponse['access_token'];
    $refreshToken = $accessTokenResponse['refresh_token'];
?>
```

### Building your own registration page
In order to build your own registration page, you can retrieve required fields using `getRegistrationSetup` with requestId and locale. 
```php
<?php
    $registrationResponse = $provider->getRequestId()->then(function ($requestId) {
        return $provider->getRegistrationSetup($requestId, $locale);
    })->wait();
    $registrationFields = $registrationResponse['data'];
?>
```
Using the fields and another requestId, you can then `register` a new customer.
```php
<?php
try {
    $registrationResponse = $provider->getRequestId()->then(function ($requestId) {
        return $provider->register($fields, $requestId);
    })->wait();
} catch (ClientException $exception) {
    $errorMessage = json_decode($exception->getResponse()->getBody())['error']['error'];
}
?>
```

### Retrieve an access token by refresh token
In order to get a new access token by a given refresh token, you can also use the method `getAccessToken` with GrantType `RefreshToken`.
```php
<?php
    $accessToken = $provider->getAccessToken(GrantType::RefreshToken, '', $refreshToken)->then(function ($response) {
        return $response['access_token'];
    })->wait();
?>
```


### Logout
In order to perform a logout, you need an access token. Using this token, you can perform a logout.
```php
<?php
    $provider->logout($accessToken);
?>
```

### Unit testing
There are different ways of integrating php unit tests. Besides mocking the `Cidaas` class, you may also add a mock handler to the constructor for low level tests. 
```php
<?php
$cidaas = new Cidaas('https://yourcidaasinstance.cidaas.de', 'client id', 'client secret', 'https://yourwebsite/redirectAfterLogin', $mockHandler);
?>
```
This `$mockHandler` is a default guzzle mock implementation created like this:
```php
<?php
$mock = new MockHandler([new Response(200, [], $mockedResponse)]);
$mockHandler = HandlerStack::create($this->mock);
?>
```
Please refer to https://docs.guzzlephp.org/en/stable/testing.html for further information.
