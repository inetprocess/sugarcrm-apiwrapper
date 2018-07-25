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

trait ClientTrait
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
}
