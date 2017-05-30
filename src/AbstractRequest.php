<?php

namespace InetProcess\SugarAPI;

use GuzzleHttp\Client;
use Webmozart\Assert\Assert;

abstract class AbstractRequest
{
    protected $baseUrl;

    protected $client;

    private $loginAttempts = 0;

    public function __construct($baseUrl, $version = 'v10', $verify = false)
    {
        Assert::boolean($verify, 'Verify must be a boolean');

        $this->normalizeUrl($baseUrl, $version);
        $this->client = new Client(['verify' => $verify]);
        $this->logger = new \Psr\Log\NullLogger;
    }

    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    protected function request($url, array $headers = [], array $data = [], $method = 'get', $expected = 201)
    {
        $fullUrl = $this->baseUrl . '/' . ltrim($url, '/');
        $this->logger->debug("SugarAPIWrapper: " . strtoupper($method) . ' ' . $fullUrl);

        $options = ['headers' => $headers];

        // trying to send a file
        if (!empty($data['filename'])) {
            Assert::keyExists($data, 'field', "You must set the field name as 'field' key to upload");
            Assert::keyExists($data, 'contents', "You must set the contents as 'contents' key to upload");
            $options['multipart'] = [
                ['name'     => $data['field'], 'contents' => $data['contents'], 'filename' => $data['filename']],
            ];
        } elseif (!empty($data)) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = json_encode($data);
        }

        try {
            $response = $this->client->request(strtoupper($method), $fullUrl, $options);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Could not resolve host') !== false) {
                $this->logger->critical('SugarAPIWrapper: ' . $msg = 'Wrong SugarCRM URL');
                throw new Exception\SugarAPIException($msg);

            } elseif ($e->getCode() === 401) {
                $this->tryToLoginAgain();
                // Refresh the token and try again
                $headers['OAuth-Token'] = $this->getToken();

                return $this->request($url, $headers, $data, $method, $expected);

            } elseif ($e->getCode() === 404) {
                $this->logger->critical('SugarAPIWrapper: ' . $msg = '404 Error - SugarCRM Endpoint not found');
                throw new Exception\SugarAPIException($msg, 404);

            } elseif ($e->getCode() === 500) {
                $this->logger->critical('SugarAPIWrapper: ' . $msg = 'SugarCRM Server Error');
                throw new Exception\SugarAPIException($msg, 500);

            }

            $this->logger->critical('SugarAPIWrapper: ' . $msg = 'Request Error: ' . $e->getMessage());
            throw new Exception\SugarAPIException($msg);
        }

        if ($expected !== $response->getStatusCode()) {
            $msg = 'Bad status, got ' . $response->getStatusCode() . '. Instead of ' . $expected . '. ';
            $msg.= 'Reponse: ' . $response->getReasonPhrase();
            $this->logger->critical('SugarAPIWrapper: ' . $msg);
            throw new Exception\SugarAPIException($msg);
        }

        $data = json_decode($response->getBody(), true);
        if ($data === null) {
            $this->logger->critical("SugarAPIWrapper: Can't read the output");
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

    private function tryToLoginAgain()
    {
        $this->logger->notice('SugarAPIWrapper: 401 Trying to log my user again as it has been disconnected');
        if ($this->loginAttempts > 5) {
            throw new \RuntimeException('Tried 5 times to login to sugar without success');
        }
        $this->login();
        $this->loginAttempts++;
    }


    public function getClient()
    {
        return $this->client;
    }
}
