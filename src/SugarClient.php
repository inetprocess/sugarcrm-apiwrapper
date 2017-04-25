<?php

namespace InetProcess\SugarAPI;

use Webmozart\Assert\Assert;

class SugarClient extends AbstractRequest
{
    /**
     * @var Client
     */
    protected $username;
    protected $password;
    protected $platform = 'inetprocess';
    protected $token;
    protected $tokenExpiration;

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    public function login()
    {
        Assert::stringNotEmpty($this->username, 'You must call setUsername() or setToken() before doing any action');
        Assert::stringNotEmpty($this->password, 'You must call setPassword() or setToken() before doing any action');
        $body = $this->request('oauth2/token', [], [
            'grant_type' => 'password',
            'client_id' => 'sugar',
            'client_secret' => '',
            'username' => $this->username,
            'password' => $this->password,
            'platform' => $this->platform,
        ], 'post', 200);

        if (empty($body['access_token'])) {
            throw new Exception\SugarAPIException("No Token in the returned body");
        }

        $this->token = $body['access_token'];
        $this->tokenExpiration = new \DateTime("+{$body['expires_in']} seconds");
    }

    public function post($url, array $data, $expectedStatus = 201)
    {
        return $this->baseRequest('post', $url, $data, $expectedStatus);
    }

    public function put($url, array $data, $expectedStatus = 200)
    {
        return $this->baseRequest('put', $url, $data, $expectedStatus);
    }

    public function get($url, $expectedStatus = 200)
    {
        return $this->baseRequest('get', $url, [], $expectedStatus);
    }

    public function delete($url, $expectedStatus = 204)
    {
        return $this->baseRequest('delete', $url, [], $expectedStatus);
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getTokenExpiration()
    {
        return $this->tokenExpiration;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    private function baseRequest($method, $url, array $data = [], $expectedStatus)
    {
        Assert::oneOf($method, ['get', 'post', 'put', 'delete'], 'You can only post, put or get');

        $now = new \DateTime;
        if (empty($this->token) || $this->tokenExpiration < $now) {
            $this->login();
        }

        $headers = ['OAuth-Token' => $this->token];

        return $this->request($url, $headers, $data, $method, $expectedStatus);
    }
}
