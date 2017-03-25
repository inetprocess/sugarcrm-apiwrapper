<?php

namespace InetProcess\SugarAPI;

use GuzzleHttp\Client;

abstract class AbstractRequest
{
    protected $baseUrl;
    
    protected $client;
    
    public function __construct($baseUrl, $version)
    {
        $this->normalizeUrl($baseUrl, $version);
        $this->client = new Client();
    }
        
    public function post($url, array $data, $expectedStatus = 201)
    {
        $this->request($url, $data, 'post', $expectedStatus);
    }
    
    protected function request($url, array $data, $method = 'get', $expectedStatus = 201)
    {
        $headers = [];
        if (!empty($data)) {
            $headers['json'] = $data;
        }
        $url = $this->baseUrl . '/' . ltrim($url, '/');
        $response = $this->client->request($method, $url, $headers);

        if ($expectedStatus !== $response->getStatusCode()) {
            throw new Exception\SugarAPIException(
                'Bad status, got ' . $response->getStatusCode() . PHP_EOL .
                'Reponse: ' . $response->getReasonPhrase()
            );
        }

        $data = json_decode($response->getBody(), true);
        if ($data === null) {
            throw new Exception\SugarAPIException(
                "Can't read the output. Status: " . $response->getStatusCode() . PHP_EOL .
                "Raw Body: " . $response->getBody()
            );
        }

        return $data;
    }
 
    protected function normalizeUrl($url, $version)
    {
        if (strpos($url, 'http') === false) {
            $url = 'http://' . $url;
        }
        // remove everything after /rest/v10
        $this->baseUrl = preg_replace('|^(https?://.+)(/rest/v.*)$|', '$1', $url);
        $this->baseUrl.= '/rest/v' . $version;
    }


    public function getClient()
    {
        return $this->getClient();
    }
}