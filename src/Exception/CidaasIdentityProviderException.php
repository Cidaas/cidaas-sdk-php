<?php
namespace Cidaas\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class CidaasIdentityProviderException extends IdentityProviderException
{
    /**
     * @param  ResponseInterface $response
     * @param  string|null $message
     * @return IdentityProviderException
     */
    public static function fromResponse(ResponseInterface $response, $message = null)
    {
        return new static($message, $response->getStatusCode(), (string) $response->getBody());
    }
}
