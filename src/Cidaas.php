<?php

namespace Cidaas\OAuth2\Client\Provider;

use Cidaas\OAuth2\Client\Provider\AbstractProvider;

class Cidaas extends AbstractProvider
{
    public function getRequestId(): string
    {
        // TODO implement
        return "";
    }

    public function loginWithCredentials($username, $username_type, $password, $requestId)
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
        // TODO auch initiateResetPassword und handleResetPassword?
        // TODO implement
    }

    public function logoutUser()
    {
        // TODO implement
    }

    public function getLoginURL(array $options = [])
    {
        // TODO do we really need this?
        $options["view_type"] = "login";
        return $this->getAuthorizationUrl($options);
    }

    public function getRegisterURL(array $options = [])
    {
        // TODO do we really need this?
        $options["view_type"] = "register";
        return $this->getAuthorizationUrl($options);
    }

    public function getLogOutURL($access_token_hint = "", $post_logout_redirect_uri = "")
    {
        // TODO do we really need this?
        return $this->endSessionURL($access_token_hint, $post_logout_redirect_uri);
    }

}
