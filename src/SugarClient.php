<?php

namespace InetProcess\SugarAPI;

class SugarClient extends AbstractRequest
{
    /**
     * @var Client
     */
    protected $username;
    protected $password;
    protected $platform;
    protected $token;

    public function __construct($baseUrl, $username, $password, $platform = 'inetprocess', $version = '10')
    {
        parent::__construct($baseUrl, $version);
        $this->username = $username;
        $this->password = $password;
        $this->platform = $platform;
    }

    public function login()
    {
        $body = $this->request('oauth2/token', [
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
    }
 
    public function post($url, array $data, $expectedStatus = 201)
    {
        if (empty($this->token)) {
            $this->login();
        }
        $this->request($url, $data, 'post', $expectedStatus);
    }
     
    public function getToken()
    {
        return $this->token;
    }
    
    public function setToken($token)
    {
        $this->token = $token;
    }
}

