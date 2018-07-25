<?php
/**
 * An API Wrapper made in PHP 5.6 for SugarCRM 7.x
 *
 * @package    sugarcrm-apiwrapper
 * @author     Emmanuel Dyan
 * @copyright  2005-2017 iNet Process
 * @version    1.0.3
 * @link       http://www.inetprocess.com
 */

namespace InetProcess\SugarAPI;

class SugarClient extends BaseRequest
{
    use ClientTrait;

    /**
     *
     */
    public function newBulkRequest()
    {
        return new BulkRequest($this);
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
