<?php

namespace InetProcess\SugarAPI\Tests;

use InetProcess\SugarAPI\BaseRequest;

class BaseRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $this->assertInstanceOf('\GuzzleHttp\Client', $base->getClient());
    }

    public function testSetLogger()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $base = $base->setLogger(new \Psr\Log\NullLogger);
        $this->assertInstanceOf('\GuzzleHttp\Client', $base->getClient());
    }

    public function testSetPlatform()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setPlatform('phpunit'));
    }

    public function testGetBaseUrl()
    {
        $base = new BaseRequest('http://test.sugar/my/sub/folder');
        $this->assertEquals('http://test.sugar/my/sub/folder/rest/v10', $base->getBaseUrl());

        $base = new BaseRequest('test.sugar/my/sub/folder');
        $this->assertEquals('http://test.sugar/my/sub/folder/rest/v10', $base->getBaseUrl());

        $base = new BaseRequest('test.sugar/my/sub/folder/rest/v12', 'v4');
        $this->assertEquals('http://test.sugar/my/sub/folder/rest/v4', $base->getBaseUrl());
    }

    /**
     * @expectedException InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Wrong SugarCRM URL
     * @group errors
     */
    public function testRequestWrongUrl()
    {
        $base = new BaseRequest('test.sugar/my/sub/folder');
        $base->request('test');
    }

    /**
     * @expectedException InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage 404 Error - SugarCRM Endpoint not found
     * @group errors
     */
    public function testRequest404()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $base->request('does/not/exist');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage You must call setUsername() or setToken() before doing any action
     * @group errors
     */
    public function testRequestNoUser()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $base->request('/Contacts');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage You must call setPassword() or setToken() before doing any action
     * @group errors
     */
    public function testRequestWrongPassword()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $base->setUsername(getenv('SUGARCRM_USER'));
        $base->request('/Contacts');
    }

    /**
     * @expectedException InetProcess\SugarAPI\Exception\SugarAPIWrongStatus
     * @expectedExceptionMessage Bad status, got 200. Instead of 800
     * @group errors
     */
    public function testRequestWrongStatus()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setUsername(getenv('SUGARCRM_USER')));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setPassword(getenv('SUGARCRM_PASSWORD')));
        $base->request('/Contacts', [], [], 'get', 800);
    }

    public function testRequestEverythingIsFine()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setUsername(getenv('SUGARCRM_USER')));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setPassword(getenv('SUGARCRM_PASSWORD')));
        $data = $base->request('/Contacts', [], [], 'get', 200);

        $this->assertNotEmpty($base->getTokenExpiration());
        $this->assertInstanceOf('\DateTime', $base->getTokenExpiration());
        $this->assertGreaterThan(new \DateTime('-1 hour'), $base->getTokenExpiration());
        $this->assertLessThan(new \DateTime('+2 hour'), $base->getTokenExpiration());

        $this->assertNotEmpty($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('records', $data);
    }

    public function testRequestGetTokenAndSetItAgain()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setUsername(getenv('SUGARCRM_USER')));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setPassword(getenv('SUGARCRM_PASSWORD')));
        $base->login();

        $data = $base->request('/Contacts', ['OAuth-Token' => $base->getToken()], [], 'get', 200);
        $this->assertNotEmpty($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('records', $data);

        $oldToken = $base->getToken();

        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setToken($oldToken));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setTokenExpiration($base->getTokenExpiration()));
        $data = $base->request('/Contacts', ['OAuth-Token' => $base->getToken()], [], 'get', 200);
        $this->assertNotEmpty($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('records', $data);

        $this->assertEquals($oldToken, $base->getToken());
    }

    public function testRequestGetTokenAndSetItWrong()
    {
        $base = new BaseRequest(getenv('SUGARCRM_URL'));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setUsername(getenv('SUGARCRM_USER')));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setPassword(getenv('SUGARCRM_PASSWORD')));
        $base->login();

        $data = $base->request('/Contacts', ['OAuth-Token' => $base->getToken()], [], 'get', 200);
        $this->assertNotEmpty($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('records', $data);

        $oldToken = $base->getToken();

        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setToken('wrong'));
        $this->assertInstanceOf('InetProcess\SugarAPI\BaseRequest', $base->setTokenExpiration($base->getTokenExpiration()));
        $data = $base->request('/Contacts', ['OAuth-Token' => $base->getToken()], [], 'get', 200);
        $this->assertNotEmpty($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('records', $data);

        $this->assertNotEquals($oldToken, $base->getToken());
    }

    /**
     * Made to test the documentation in README
     */
    public function testDoc()
    {
        $url = getenv('SUGARCRM_URL');
        $username = getenv('SUGARCRM_USER');
        $password = getenv('SUGARCRM_PASSWORD');
        #### START DOC EXAMPLE
        // $url = 'http://127.0.0.1';
        // $username = 'admin';
        // $password = 'admin';

        $base = new BaseRequest($url);
        $base->setUsername($username)->setPassword($password);

        // The login can be called dynamically as the class will detect you are not logged
        // But to save an API Request, call it manually
        $base->login();

        // Get the list of Contacts
        // '200' is the expected Status Code. If it's not the right one, you'll get an Exception
        $data = $base->request('/Contacts', ['OAuth-Token' => $base->getToken()], [], 'get', 200);
        #### END DOC

        $this->assertNotEmpty($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('records', $data);
    }
}
