<?php
/**
 * An API Wrapper made in PHP 5.6 for SugarCRM 7.x
 *
 * @version    1.0-beta1
 * @package    sugarcrm-apiwrapper
 *
 * @author     Emmanuel Dyan
 * @copyright  2005-2017 iNet Process
 *
 * @link       http://www.inetprocess.com
 */

namespace InetProcess\SugarAPI;

class SugarClient extends BaseRequest
{
    /**
     * @param  string $url
     * @param  int $expectedStatus
     * @return array
     */
    public function delete($url, $expectedStatus = 200)
    {
        return $this->clientRequest('delete', $url, $expectedStatus);
    }

    /**
     * @param  string $url
     * @param  int $expectedStatus
     * @return array
     */
    public function get($url, $expectedStatus = 200, $rawBody = false)
    {
        return $this->clientRequest('get', $url, $expectedStatus, [], [], $rawBody);
    }

    /**
     * @param  string $url
     * @param  array             $data
     * @param  int $expectedStatus
     * @return array
     */
    public function post($url, array $data, $expectedStatus = 200)
    {
        return $this->clientRequest('post', $url, $expectedStatus, $data);
    }

    /**
     * @param  string $url
     * @param  array             $data
     * @param  int $expectedStatus
     * @return array
     */
    public function put($url, array $data, $expectedStatus = 200)
    {
        foreach ($data as $field => $value) {
            if (is_null($value)) {
                $data[$field] = '';
            }
        }

        return $this->clientRequest('put', $url, $expectedStatus, $data);
    }

    /**
     * @param  string  $method
     * @param  string  $url
     * @param  int     $expect
     * @param  array   $data
     * @param  array   $headers
     * @return array
     */
    private function clientRequest($method, $url, $expect = 200, array $data = [], array $headers = [], $raw = false)
    {
        $now = new \DateTime;
        if (empty($this->token) || $this->tokenExpiration < $now) {
            $this->logger->debug('SugarAPIWrapper Client: Token ' . empty($this->token) ? 'Empty' : 'Expired');
            $this->login();
        }

        $headers = array_merge(['OAuth-Token' => $this->token], $headers);

        return $this->request($url, $headers, $data, $method, $expect, $raw);
    }
}
