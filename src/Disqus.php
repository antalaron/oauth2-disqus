<?php

/*
 * This file is part of Disqus Oaut2 client
 *
 * (c) EDIMA.email Kft. <admin24@edima.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\DisqusOAuth2;

use Antalaron\DisqusOAuth2\Exception\DisqusIdentityProviderException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
class Disqus extends AbstractProvider
{
    public $domain = 'https://disqus.com';

    public function getBaseAuthorizationUrl()
    {
        return $this->domain.'/api/oauth/2.0/authorize/';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain.'/api/oauth/2.0/access_token/';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domain.'/api/3.0/users/details.json?access_token='.$token
            .'&api_key='.$this->clientId.'&api_secret='.$this->clientSecret;
    }

    protected function getDefaultScopes()
    {
        return [
            'read',
        ];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw DisqusIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw DisqusIdentityProviderException::oauthException($response, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new DisqusResourceOwner($response);
    }
}
