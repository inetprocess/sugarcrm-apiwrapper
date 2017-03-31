<?php

namespace InetProcess\SugarAPI;

use GuzzleHttp\Client;
use Webmozart\Assert\Assert;

abstract class AbstractRequest
{
    protected $baseUrl;

    protected $client;

    public function __construct($baseUrl, $version = 'v10', $verify = false)
    {
        Assert::boolean($verify, 'Verify must be a boolean');
        $this->normalizeUrl($baseUrl, $version);
        $this->client = new Client(['verify' => $verify]);
    }

    protected function request($url, array $headers = [], array $data = [], $method = 'get', $expected = 201)
    {
        $url = $this->baseUrl . '/' . ltrim($url, '/');
        $options = ['headers' => $headers];
        if (!empty($data)) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = json_encode($data);
        }

        try {
            $response = $this->client->request(strtoupper($method), $url, $options);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Could not resolve host') !== false) {
                throw new Exception\SugarAPIException('Wrong SugarCRM URL');
            } elseif ($e->getCode() === 404) {
                throw new Exception\SugarAPIException('404 Error: SugarCRM Endpoint not found', 404);
            } elseif ($e->getCode() === 500) {
                throw new Exception\SugarAPIException('SugarCRM Server Error', 500);
            }
            throw new Exception\SugarAPIException('Request Error: ' . $e->getMessage());
        }

        if ($expected !== $response->getStatusCode()) {
            throw new Exception\SugarAPIException(
                'Bad status, got ' . $response->getStatusCode() . PHP_EOL .
                'Instead of ' . $expected . PHP_EOL .
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
        $this->baseUrl.= '/rest/' . $version;
    }


    public function getClient()
    {
        return $this->getClient();
    }
}
