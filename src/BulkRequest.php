<?php
/**
 * An API Wrapper made in PHP 5.6 for SugarCRM 7.x
 *
 * @package    sugarcrm-apiwrapper
 * @author     Rémi Sauvat
 * @copyright  2005-2017 iNet Process
 * @version    1.0.3
 * @link       http://www.inetprocess.com
 */

namespace InetProcess\SugarAPI;

class BulkRequest
{
    use ClientTrait;

    protected $client;

    protected $requests = [];

    protected $responses = [];

    public function __construct(SugarClient $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getVersion()
    {
        return $this->client->getVersion();
    }

    public function getRequests()
    {
        return $this->requests;
    }

    public function send()
    {
        $this->responses = $this->getClient()->post('bulk', [
            'requests' => array_map([$this, 'filterRequestKeys'], $this->requests),
        ], 200);

        foreach ($this->responses as $key => $response) {
            $this->responses[$key]['request'] = $this->requests[$key];
        }
        return $this->responses;
    }

    public function filterRequestKeys($request)
    {
        if (array_key_exists('expected_code', $request)) {
            unset($request['expected_code']);
        }
        return $request;
    }

    public function getErrors()
    {
        return array_filter($this->responses, [$this, 'isStatusError']);
    }

    public function isStatusError($response)
    {
        return $response['status'] == $response['request']['expected_code'];
    }

    public function clientRequest($method, $endpoint, $expectedCode = 200, array $data = [], array $headers = [])
    {
        $request = [
            'method' => strtoupper($method),
            'url' => sprintf('/%s/%s', $this->getVersion(), ltrim($endpoint, '/')),
            'expected_code' => $expectedCode,
        ];
        if (!empty($headers)) {
            $request['headers'] = $headers;
        }
        if (!empty($data)) {
            $request['data'] = json_encode($data);
        }
        $this->requests[] = $request;
    }
}
