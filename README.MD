![Logo](logo.jpg)

## About cidaas:
[cidaas](https://www.cidaas.com)
 is a fast and secure Cloud Identity & Access Management solution that standardises what’s important and simplifies what’s complex.

## Feature set includes:
* Single Sign On (SSO) based on OAuth 2.0, OpenID Connect, SAML 2.0 
* Multi-Factor-Authentication with more than 14 authentication methods, including TOTP and FIDO2 
* Passwordless Authentication 
* Social Login (e.g. Facebook, Google, LinkedIn and more) as well as Enterprise Identity Provider (e.g. SAML or AD) 
* Security in Machine-to-Machine (M2M) and IoT

# Cidaas SDK for PHP

### Requirements

Make sure you have installed all of the following prerequisites on your development machine:
 - PHP version 7.4.0 or higher
 - Download and install the composer

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

### Integration of hosted registration page

In order to integrate a hosted register page, you might just implement a simple button which itself calls the register method when clicked. The function accepts the below parameters

| Param Name | Type | is optional| Default value | Description |
|------ | ---- | ----------- | --------- | ---------- |
| scope | string | true | 'openid profile offline_access' | scope of the registered user |
| queryParameters | associative array | true | [] | additional query params to add to the url. eg: ["local" => "en-US"] |

```php
<?php
    $provider->registerWithBrowser();
?>
```
After successful registration, the browser is redirected to the login page for you to login with the registered details. The reirection may sometimes differ based on the configuration of the client.

### Integration of social login page

To integrate social login redirection, you might just implement a simple button which itself calls the login method when clicked. In order to access the login method, you need to retrieve a requestId first. The function requires the provider name(e.g: google, facebbok) and the requestId as functional parameter and optinally more query params can be added to the request url.

```php
<?php
    $provider->getRequestId('openid identities profile offline_access')->then(function ($requestId) {
        $provider->loginWithSocial('google', $requestId);
    })
?>
```
This will redirect to the login page of the social provider. After successful login, the browser is redirected to your selected redirectUri (see Prerequisites).

### Integration of social registration page

To integrate social registration redirection, you might just implement a simple button which itself calls the register method when clicked. In order to access the register method, you need to retrieve a requestId first. The function requires the provider name(e.g: google, facebbok) and  the requestId as functional parameter and optionally more query params can be added to the request url.

```php
<?php
    $provider->getRequestId('openid identities profile offline_access')->then(function ($requestId) {
        $provider->registerWithSocial('google', $requestId);
    })
?>
```
This will redirect to the register page of the social provider. After successful registration, you can call the function loginWithSocial to start loging in to your cidaas client with the social provider.

### Initiate multi factor authentication

To initiate multi factor authentication use the function intiateMFA. You need to retrieve a requestId first. Allowed types are mentioned in the list below
```
    EMAIL
    SMS
    IVR
    BACKUPCODE
    SECURITY_QUESTION
    TOTP
    PATTERN
    PUSH
    FACE
    TOUCHID
    VOICE
    FIDOU2F
    FIDO2
    SEALONE
    PASSWORD
```

```php
<?php
    $resp = $provider->getRequestId('openid identities profile offline_access')->then(function ($requestId) {
        $params = [
            "email" => 'account@cidaas.de'
            "request_id" => $requestId,
            "usage_type" => "MULTIFACTOR_AUTHENTICATION"
        ]
        $resp = $provider->intiateMFA('EMAIL', $params)->wait();
    })
?>
```

The api used in the function returns a response like shown below. You will require the exchange_id and sub from the response in order to complete the authentication process by calling the function authenticateMFA.

```json
{
	"success": true,
	"status": 200,
	"data": {
		"exchange_id": {
			"exchange_id": "a64782cd-d136-4fc3-a879-3afb4feb7453",
			"expires_at": "2023-11-20T07:37:01.439Z",
			"_id": "0d69a2b7-4a2c-4776-97bf-09da5d243e02",
			"createdTime": "2023-11-20T07:07:01.440Z",
			"updatedTime": "2023-11-20T07:07:01.440Z",
			"__ref": "58dfddbc8e3777f5:2a4e7a910c19bc67:58dfddbc8e3777f5:0",
			"id": "0d69a2b7-4a2c-4776-97bf-09da5d243e02"
		},
		"medium_text": "account@cidaas.de",
		"sub": "3e851ef6-d6f4-41c3-8ff0-8277279c0fbd",
		"status_id": "398ff79e-af29-4889-8377-f82dbf46ed63"
	}
}
```

### Authenticate multi factor

To authenticate multi factor you must first initiate the authentication process with the help of the function intiateMFA. You will be shared a passcode/verification code based on the type of authentication you prefer in the initiaition process. The types allowed for the authentication process are mentioned in the above section.

```php
<?php
    $resp = $provider->authenticateMFA('a64782cd-d136-4fc3-a879-3afb4feb7453', '3e851ef6-d6f4-41c3-8ff0-8277279c0fbd', '123456', 'EMAIL')->wait();
?>
```

The api used in the function returns a response like shown below. After successful authentication you may proceed with your custom implementation. For any failure the attibute "success" in the response payload will have value false.

```json
{
	"success": true,
	"status": 200,
	"data": {
		"exchange_id": {
			"exchange_id": "a64782cd-d136-4fc3-a879-3afb4feb7453",
			"expires_at": "2023-11-20T07:37:01.439Z",
			"_id": "0d69a2b7-4a2c-4776-97bf-09da5d243e02",
			"createdTime": "2023-11-20T07:07:01.440Z",
			"updatedTime": "2023-11-20T07:07:01.440Z",
			"__ref": "58dfddbc8e3777f5:2a4e7a910c19bc67:58dfddbc8e3777f5:0",
			"id": "0d69a2b7-4a2c-4776-97bf-09da5d243e02"
		},
		"medium_text": "account@cidaas.de",
		"sub": "3e851ef6-d6f4-41c3-8ff0-8277279c0fbd",
		"status_id": "398ff79e-af29-4889-8377-f82dbf46ed63"
	}
}
```

### Initiate account verification

To initiate account verification use the function initiateAccountVerification. Account verification is done for newly created user account is not verified. A sample of the function parameter $params is shown below

* Allowed values for verificationMedium

```
    email
    sms
    ivr
```

* Allowed values for verificationMedium

```
    CODE
    LINK
```

```php
<?php
    $params = [
        "response_type": "token",
        "verificationMedium": "email",
        "processingType": "code",
        "client_id": "103a3f9c-1a2d-4e47-940k-bad7f19d9604",
        "redirect_uri": "https://test.cidaas.com/user-profile/editprofile",
        "email": "account@cidaas.de"
    ]
    $provider->initiateAccountVerification($params)->wait();
?>
```

Once initiated successfully a passcode or a link will be sent to the preferred verificationMedium to verify your account

### Verify account verificationMedium CODE

To complete account verification use the function verifyAccount. You will need the accvid which you will receive at the time of account verification initiation process.

```php
<?php
    $resp = $provider->verifyAccount("103a3f9c-1a2d-4e47-940k-bad7f19d9604", "123456")->wait();
?>
```

The api used in the function returns a response like shown below. For failed verification the value of the attribute success will be false. After successful verification you may proceed with your custom implementation.

```json
{
    "success": true,
    "status": 200
}
```

### Perform progressive registration

Initiates progressive registration for missing required registration fields after an account is created in cidaas system. Below are the fields which can be updated in the process of progessive registration. All these fields need to passed in the function parameter $params

| field name       | type         |
|------------------|--------------|
| userStatus| string |
| user_status| string |
| user_status_reason| string |
| username| string |
| sub| string |
| given_name| string |
| family_name| string |
| middle_name| string |
| nickname| string |
| originalProviderUserId| string[] |
| email| string |
| email_verified| boolean |
| mobile_number| string |
| mobile_number_verified| boolean |
| phone_number| string |
| phone_number_verified| boolean |
| profile| string |
| picture| string |
| website| string |
| gender| string |
| zoneinfo| string |
| locale| string |
| birthdate| Date | null |
| address| IAddressEntity |
| customFields| any |
| identityCustomFields| any |
| password| string |
| provider| string |
| providerUserId| string |
| identityId| string |
| mfa_enabled| boolean |
| roles| string[] |
| userGroups| IUserGroupMap[] |
| groups| IUserGroupMap[] |
| rawJSON| string |
| trackId| string |
| need_reset_password| boolean |

The type IAddressEntity and IUserGroupMap contains the below fields

#### IAddressEntity

| field name       | type         |
|------------------|--------------|
| formatted| string |
| street_address| string |
| locality| string |
| region| string |
| postal_code| string |
| country| string |

#### IUserGroupMap

| field name       | type         |
|------------------|--------------|
| sub| string |
| groupId| string |
| roles| string[] |
| appendRole| boolean |

```php
<?php
    $params = [
        "given_name": "Firstname",
        "username": "test_username",
        "family_name": "Lastname",
        "mobile_number": "7865637869"
    ]
    $resp = $provider->progressiveRegistration("103a3f9c-1a2d-4e47-940k-bad7f19d9604", "203a3f9c-1a2d-4e47-940k-bad7f19d9604", $params)->wait();
?>
```

### Get consent details

To get the consent details of a client use the function getConsentDetails. The function accepts 3 parameters consent_id, consent_version_id and sub.

```php
<?php
    $resp = $provider->getConsentDetails("103a3f9c-1a2d-4e47-940k-bad7f19d9604", "1",  "503a3f9c-1a2d-4e47-940k-bad7f19d960")->wait();
?>
```

### Accept consent details

To accept consent by sub use the function acceptConsent. The function accepts a parameter of type array. Below are the fields that need to be passed in the array. A sample array is shown in the example below

| field | type |
|------ | ---- |
| client_id | string |
| consent_id | string |
| consent_version_id | string |
| sub | string |
| scopes | string[] |
| url | string |
| matcher | any |
| field_key | string |
| accepted_fields | string[] |
| accepted_by | string |
| skipped | boolean |
| action_type | string |
| action_id | string |
| q | string |
| revoked | boolean |

```php
<?php
     $params = [
        "client_id": "1234567",
        "consent_id": "1234567",
        "consent_version_id": "1",
        "sub": "1234567",
        "url" : "https://cidaas.de/accept-consent"
    ]
    $resp = $provider->acceptConsent($params)->wait();
?>
```

### Get mfa list

Get the list of the mfa configured for an user use the function getMFAList. In order to get the list, you need to retrieve a requestId first. The function additionally accepts one of the below parameters

```
email
mobile_number
username
sub
```;

```php
<?php
    $provider->getRequestId('openid identities profile offline_access')->then(function ($requestId) {
        $resp = $provider->getMFAList($requestId, "account@cidaas.de")->wait();
    })
?>
```

### Initiate passwordless login

To initate passwordless login use the function initiatePasswordlessLogin. In order to initiate the process, you need to retrieve a requestId first. The function additionally accepts the below params

| field | type |
|------ | ---- |
| request_id | string |
| type | string |
| email | string |
| sub | string |

```php
<?php
    $provider->getRequestId('openid identities profile offline_access')->then(function ($requestId) {
        $resp = $provider->initiatePasswordlessLogin($requestId, "email", 'account@cidaas.de', '1234567')->wait();
    })
?>
```

### Verify passwordless login

To verify passwordless login after initiating the process use the function verifyPasswordlessLogin. The function additionally accepts the below params

| field | type | description |
|------ | ---- | ----------- |
| type | string | preferred type of passwordless login. eg: email, totp |
| exchange_id | string | exchange_id received in the response body while initiating the passwordless login |
| pass_code | string | the passcode generated in the preferred type. eg: in case of email you will receive a passcode you the email provided |
| requestId | string | use the same requestId that is being used while initiating the passwordless login |

```php
<?php

    $provider->getRequestId('openid identities profile offline_access')->then(function ($requestId) {
        $resp1 = $provider->initiatePasswordlessLogin($requestId, "email", 'account@cidaas.de', '1234567')->wait();
        $resp2 = $provider->verifyPasswordlessLogin("email",$resp1->exchange_id, '123456', $requestId)->wait();
    })
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
