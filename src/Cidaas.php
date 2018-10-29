<?php
namespace Cidaas\OAuth2\Client\Provider;

use Cidaas\OAuth2\Client\Provider\AbstractProvider;

class Cidaas extends AbstractProvider
{

    public function getLoginURL(array $options = [])
    {
        $options["view_type"] = "login";
        return $this->getAuthorizationUrl($options);
    }

    public function getRegisterURL(array $options = [])
    {
        $options["view_type"] = "register";
        return $this->getAuthorizationUrl($options);
    }

    public function getLogOutURL($access_token_hint = "", $post_logout_redirect_uri = "")
    {
        return $this->endSessionURL($access_token_hint, $post_logout_redirect_uri);
    }

}
