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

namespace InetProcess\SugarAPI\Exception;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;

class SugarAPIException extends \GuzzleHttp\Exception\BadResponseException
{
    protected $errorKey;

    protected $errorMessage;

    public function __construct(
        $message,
        RequestInterface $request,
        ResponseInterface $response = null,
        $errorKey = null,
        $errorMessage = null,
        \Exception $previous = null,
        array $handlerContext = []
    ) {
        $this->errorKey = $errorKey;
        $this->errorMessage = $errorMessage;
        parent::__construct($message, $request, $response, $previous, $handlerContext);
    }

    protected static function parseJsonError($response = null)
    {
        $ret = [
            'key' => null,
            'message' => null,
        ];
        if ($response !== null && $response->getHeaderLine('Content-Type') == 'application/json') {
            $error_data = json_decode($response->getBody(), true);
            if (isset($error_data['error'])) {
                $ret['key'] = $error_data['error'];
            }
            if (isset($error_data['error_message'])) {
                $ret['message'] = $error_data['error_message'];
            }
        }
        return $ret;
    }

    public static function wrapGuzzleException(\GuzzleHttp\Exception\RequestException $e, $sugar_error = [])
    {
        $sugar_error = array_replace(self::parseJsonError($e->getResponse()), $sugar_error);
        $message = 'SugarApi '.explode("\n", $e->getMessage(), 2)[0];
        $message .= vsprintf(' [%s] %s', $sugar_error);

        return new static(
            $message,
            $e->getRequest(),
            $e->getResponse(),
            $sugar_error['key'],
            $sugar_error['message'],
            $e
        );
    }

    public static function create(
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null,
        array $ctx = []
    ) {
        $e = parent::create($request, $response, $previous, $ctx);
        return self::wrapGuzzleException($e, $ctx);
    }

    public function getErrorKey()
    {
        return $this->errorKey;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
