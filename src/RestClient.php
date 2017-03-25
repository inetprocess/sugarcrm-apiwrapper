<?php

namespace InetProcess\SugarAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class RestClient
{
    /**
     * @var Client
     */
    protected $client;
    protected $sugarUrl;
    protected $token;

    public function __construct($sugarUrl, $version = '10')
    {
        $this->normalizeUrl($sugarUrl, $version);
        $this->client = new Client();
    }

    public function login($username, $password, $platform = 'inetprocess')
    {
        $body = $this->request('oauth2/token', [
			'grant_type' => 'password',
			'client_id' => 'sugar',
			'client_secret' => '',
            'username' => $username,
            'password' => $password,
            'platform' => $platform,
        ], 'post', 200);

		if (empty($body['access_token'])) {
			throw new Exception\SugarAPIException("No Token in the returned body");
		}
		
        $this->token = $body['access_token'];
    }

    public function getClient()
    {
        return $this->getClient();
    }

    protected function request($url, array $data, $method = 'get', $expectedStatus = 201)
    {
		$headers = [];
		if (!empty($data)) {
			$headers['json'] = $data;
		}
        $response = $this->client->request($method, "{$this->sugarUrl}/$url", $headers);

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

    protected function normalizeUrl($sugarUrl, $version)
    {
        if (strpos($sugarUrl, 'http') === false) {
            $sugarUrl = 'http://' . $sugarUrl;
        }
        // remove everything after /rest/v10
        $this->sugarUrl = preg_replace('|^(https?://.+)(/rest/v.*)$|', '$1', $sugarUrl);
        $this->sugarUrl.= '/rest/v' . $version;
    }
}
