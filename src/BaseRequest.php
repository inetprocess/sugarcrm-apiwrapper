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

use Webmozart\Assert\Assert;
use GuzzleHttp\Psr7\Request;

/**
 * A very basic class that does a request to a SugarCRM Url and maintain
 */
class BaseRequest
{
    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var InetProcess\SugarAPI\SugarClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $platform = 'inetprocess';

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $tokenExpiration;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var int
     */
    private $loginAttempts = 0;

    /**
     * Initiate the Guzzle client
     * @param string $baseUrl  The base URL of SugarCRM
     * @param string $version  SugarCRM Api Version
     * @param bool   $verify
     * @param array  $options Options to be passed to GuzzleClient
     */
    public function __construct($baseUrl, $version = 'v10', $verify = false, $options = [])
    {
        Assert::boolean($verify, 'Verify must be a boolean');
        Assert::array($options, 'Options must be an array');

        $this->version = $version;
        $this->normalizeUrl($baseUrl, $version);
        $this->client = new \GuzzleHttp\Client(empty($options) ? ['verify' => $verify, 'cookies' => true] : $options);
        $this->logger = new \Psr\Log\NullLogger;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return GuzzleHttp\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getTokenExpiration()
    {
        return $this->tokenExpiration;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Login to SugarCRM
     */
    public function login()
    {
        Assert::stringNotEmpty($this->username, 'You must call setUsername() or setToken() before doing any action');
        Assert::stringNotEmpty($this->password, 'You must call setPassword() or setToken() before doing any action');

        $this->logger->debug('SugarAPIWrapper Client: Login');

        $body = $this->request('oauth2/token', [], [
            'grant_type' => 'password',
            'client_id' => 'sugar',
            'client_secret' => '',
            'username' => $this->username,
            'password' => $this->password,
            'platform' => $this->platform,
        ], 'post', 200);

        if (empty($body['access_token'])) {
            throw new \RuntimeException("No Token in the returned body");
        }

        $this->token = $body['access_token'];
        $this->tokenExpiration = new \DateTime("+{$body['expires_in']} seconds");

        $this->logger->debug('SugarAPIWrapper Client: Token is ' . $this->token);
        $this->logger->debug('SugarAPIWrapper Client: Expiration is ' . $this->tokenExpiration->format('Y-m-d H:i:s'));
    }

    /**
     * HTTP Request without any header specific to Sugar (must be set by other, as we don't need it everytime)
     * @param  string  $url
     * @param  array   $headers
     * @param  array   $data
     * @param  string  $method
     * @param  int     $expect   Expected code else throw an Exception
     * @param  bool    $raw      Returns the raw body, don't decode it
     * @return array
     */
    public function request($url, array $headers = [], array $data = [], $method = 'get', $expect = 201, $raw = false)
    {
        $fullUrl = $this->baseUrl . '/' . ltrim($url, '/');
        $this->logger->debug("SugarAPIWrapper: " . strtoupper($method) . ' ' . $fullUrl);

        $options = $this->buildOptions($headers, $data);
        try {
            $request = new Request(strtoupper($method), $fullUrl);
            $response = $this->client->send($request, $options);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            switch ($e->getCode()) {
                case 401:
                    $this->tryToLoginAgain();
                    $headers['OAuth-Token'] = $this->getToken(); // Refresh the token and try again

                    // We managed to login, run the original query again
                    return $this->request($url, $headers, $data, $method, $expect, $raw);

                default:
                    throw $this->criticalError($e);
            }
        } catch (\Exception $e) {
            $this->logger->critical('SugarApi Error: '.$e->__toString());
            throw $e;
        }

        if ($expect !== $response->getStatusCode()) {
            $msg = 'Bad status, got ' . $response->getStatusCode() . '. Instead of ' . $expect;
            $e = Exception\SugarAPIWrongStatus::create($request, $response, null, ['message' => $msg]);
            $this->logger->critical($e->getMessage());
            throw $e;
        }

        if ($raw === true) {
            return (string) $response->getBody();
        }

        $data = json_decode($response->getBody(), true);
        if (is_null($data) === null && !is_null($response->getBody())) {
            $msg = "Can't read the output. Status: " . $response->getStatusCode() . PHP_EOL;
            $msg .= "Raw Body: " . $response->getBody();

            $this->logger->critical($msg);
            throw new \RuntimeException($msg);
        }

        return $data;
    }

    /**
     * @param  Psr\Log\LoggerInterface                $logger
     * @return InetProcess\SugarAPI\AbstractRequest
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param  string                                 $password
     * @return InetProcess\SugarAPI\AbstractRequest
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @param  string                                 $platform
     * @return InetProcess\SugarAPI\AbstractRequest
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @param  string                                 $token
     * @return InetProcess\SugarAPI\AbstractRequest
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @param  \DateTime                              $tokenExpiration
     * @return InetProcess\SugarAPI\AbstractRequest
     */
    public function setTokenExpiration(\DateTime $tokenExpiration)
    {
        $this->tokenExpiration = $tokenExpiration;

        return $this;
    }

    /**
     * @param  string                                 $username
     * @return InetProcess\SugarAPI\AbstractRequest
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Rebuild the SugarCRM API URL
     * @param string $url
     * @param string $version
     */
    protected function normalizeUrl($url, $version)
    {
        if (strpos($url, 'http') === false) {
            $url = 'http://' . $url;
        }
        // remove everything after /rest/v10
        $this->baseUrl = preg_replace('|^(https?://.+)(/rest/v.*)$|', '$1', $url);
        $this->baseUrl .= '/rest/' . $version;
    }

    /**
     * Build the headers according to what we find in headers / data to send to the API
     * @param  array   $headers
     * @param  array   $data
     * @return array
     */
    private function buildOptions(array $headers, array $data)
    {
        $options = ['headers' => $headers];

        // trying to send a file
        if (!empty($data['filename']) && !empty($data['field'])) {
            Assert::keyExists($data, 'contents', "You must set the contents as 'contents' key to upload");
            $options['multipart'] = [
                ['name' => $data['field'], 'contents' => $data['contents'], 'filename' => $data['filename']],
            ];
        } elseif (!empty($data)) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = json_encode($data);
        }

        return $options;
    }


    /**
     * Throw an Exception is something bad happens
     * @param string $msg
     * @param int    $code
     */
    private function criticalError($exception)
    {
        $sugarException = Exception\SugarAPIException::wrapGuzzleException($exception);
        $this->logger->critical('SugarAPIWrapper: ' . $sugarException->getMessage());
        return $sugarException;
    }

    /**
     * If we get disconnected, try to login again and again
     */
    private function tryToLoginAgain()
    {
        $this->logger->notice('SugarAPIWrapper: 401 Trying to log my user again as it has been disconnected');
        if ($this->loginAttempts > 5) {
            throw new \RuntimeException(
                'Tried 5 times to login to sugar without success. Please verify username and password'
            );
        }
        $this->loginAttempts++;
        $this->login();
        $this->loginAttempts = 0;
    }
}
