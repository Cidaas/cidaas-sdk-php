<?php

namespace Cidaas\OAuth2\Client\Provider;

use Cidaas\OAuth2\Client\Provider\AbstractProvider;

class Cidaas extends AbstractProvider
{

    public function loginWithBrowser($username, $username_type, $password, $requestId)
    {
        // TODO implement
    }

    public function getRegistrationSetup()
    {
        // TODO implement
    }

    public function registerUser()
    {
        // TODO implement
    }

    public function updateProfile()
    {
        // TODO implement
    }

    public function getUserProfile()
    {
        // TODO implement
    }

    public function changePassword()
    {
        // TODO implement
    }

    public function resetPassword()
    {
        // TODO auch initiateResetPassword und handleResetPassword? - ja!
        // TODO implement
    }

    // accessTokenStorage

    public function logoutUser()
    {
        // TODO implement
    }

    public function getLoginUrl(array $options = [])
    {
        $options["view_type"] = "login";
        return $this->getAuthorizationUrl($options);
    }

    public function getRegisterUrl(array $options = [])
    {
        $options["view_type"] = "register";
        return $this->getAuthorizationUrl($options);
    }

    public function getLogoutURL($access_token_hint = "", $post_logout_redirect_uri = "")
    {
        // TODO do we really need this?
        return $this->endSessionURL($access_token_hint, $post_logout_redirect_uri);
    }

}
