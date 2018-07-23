<?php

namespace InetProcess\SugarAPI\Tests;

use InetProcess\SugarAPI\BaseRequest;
use InetProcess\SugarAPI\Exception\SugarAPIException;
use GuzzleHttp\Psr7\Request;

class ExceptionsTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateException()
    {
        $e = SugarAPIException::create(new Request('POST', 'https://example.org'));
        $this->assertNull($e->getErrorKey());
        $this->assertNull($e->getErrorMessage());
    }

    public function testRequestNotFound()
    {
        try {
            $base = new BaseRequest(getenv('SUGARCRM_URL'));
            $username = getenv('SUGARCRM_USER');
            $password = getenv('SUGARCRM_PASSWORD');
            $base->setUsername($username)->setPassword($password);
            $base->login();
            $base->request('/Contacts/1234', ['OAuth-Token' => $base->getToken()], [], 'get', 200);
        } catch (SugarAPIException $e) {
            $this->assertEquals('not_found', $e->getErrorKey());
            $this->assertEquals('Could not find record: 1234 in module: Contacts', $e->getErrorMessage());
        }
    }
}
