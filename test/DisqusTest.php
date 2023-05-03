<?php

/*
 * This file is part of Disqus Oaut2 client
 *
 * (c) EDIMA.email Kft. <admin24@edima.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\OAuth2\Client\Test\Provider;

use Antalaron\DisqusOAuth2\DisqusResourceOwner;
use GuzzleHttp\Psr7\Stream;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use PHPUnit\Framework\TestCase;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
class DisqusTest extends TestCase
{
    use QueryBuilderTrait;

    protected ?\Antalaron\DisqusOAuth2\Disqus $provider;

    protected function setUp(): void
    {
        $this->provider = new \Antalaron\DisqusOAuth2\Disqus([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    protected function tearDown(): void
    {
        \Mockery::close();

        parent::tearDown();
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes(): void
    {
        $scopeSeparator = ',';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $this->provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);

        $this->assertStringContainsString($encodedScope, $url);
    }

    public function testGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/api/oauth/2.0/authorize/', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl(): void
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/api/oauth/2.0/access_token/', $uri['path']);
    }

    public function testGetAccessToken(): void
    {
        /** @var \Psr\Http\Message\ResponseInterface&\Mockery\MockInterface */
        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')
            ->andReturn(new Stream(fopen(sprintf('data://text/plain,%s', '{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}'), 'r')))
        ;
        $response->shouldReceive('getHeader')
            ->andReturn(['content-type' => 'json'])
        ;
        $response->shouldReceive('getStatusCode')
            ->andReturn(200)
        ;

        /** @var \GuzzleHttp\ClientInterface&\Mockery\MockInterface */
        $client = \Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testUserData(): void
    {
        $userId = rand(1000, 9999);
        $name = uniqid();
        $username = uniqid();

        /** @var \Psr\Http\Message\ResponseInterface&\Mockery\MockInterface */
        $postResponse = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')
            ->andReturn(new Stream(fopen(sprintf('data://text/plain,%s', http_build_query([
                'access_token' => 'mock_access_token',
                'expires' => 3600,
                'refresh_token' => 'mock_refresh_token',
            ])), 'r')))
        ;
        $postResponse->shouldReceive('getHeader')
            ->andReturn(['content-type' => 'application/x-www-form-urlencoded'])
        ;
        $postResponse->shouldReceive('getStatusCode')
            ->andReturn(200)
        ;

        /** @var \Psr\Http\Message\ResponseInterface&\Mockery\MockInterface */
        $userResponse = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')
            ->andReturn(new Stream(fopen(sprintf('data://text/plain,%s', json_encode([
                'response' => [
                    'id' => $userId,
                    'name' => $name,
                    'username' => $username,
                ],
            ])), 'r')))
        ;
        $userResponse->shouldReceive('getHeader')
            ->andReturn(['content-type' => 'json'])
        ;
        $userResponse->shouldReceive('getStatusCode')
            ->andReturn(200)
        ;

        /** @var \GuzzleHttp\ClientInterface&\Mockery\MockInterface */
        $client = \Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse)
        ;
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        /** @var DisqusResourceOwner */
        $user = $this->provider->getResourceOwner($token);

        $this->assertInstanceOf(DisqusResourceOwner::class, $user);
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['response']['id']);
        $this->assertEquals($name, $user->getName());
        $this->assertEquals($name, $user->toArray()['response']['name']);
        $this->assertEquals($username, $user->getUsername());
        $this->assertEquals($username, $user->toArray()['response']['username']);
    }

    public function testExceptionThrownWhenErrorObjectReceived(): void
    {
        $status = rand(400, 600);

        /** @var \Psr\Http\Message\ResponseInterface&\Mockery\MockInterface */
        $postResponse = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')
            ->andReturn(new Stream(fopen(sprintf('data://text/plain,%s', json_encode([
                'message' => 'Validation Failed',
                'errors' => [
                    ['resource' => 'Issue', 'field' => 'title', 'code' => 'missing_field'],
                ],
            ])), 'r')))
        ;
        $postResponse->shouldReceive('getHeader')
            ->andReturn(['content-type' => 'json'])
        ;
        $postResponse->shouldReceive('getStatusCode')
            ->andReturn($status)
        ;

        /** @var \GuzzleHttp\ClientInterface&\Mockery\MockInterface */
        $client = \Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse)
        ;
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testExceptionThrownWhenOAuthErrorReceived(): void
    {
        $status = 200;

        /** @var \Psr\Http\Message\ResponseInterface&\Mockery\MockInterface */
        $postResponse = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')
            ->andReturn(new Stream(fopen(sprintf('data://text/plain,%s', json_encode([
                'error' => 'bad_verification_code',
            ])), 'r')))
        ;
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        /** @var \GuzzleHttp\ClientInterface&\Mockery\MockInterface */
        $client = \Mockery::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse)
        ;
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);

        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
