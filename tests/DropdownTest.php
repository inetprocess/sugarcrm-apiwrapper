<?php

namespace InetProcess\SugarAPI\Tests;

use InetProcess\SugarAPI\Exception\SugarAPIException;
use InetProcess\SugarAPI\Dropdown;
use InetProcess\SugarAPI\SugarClient;

class DropdownTest extends \PHPUnit_Framework_TestCase
{
    private $dropdown;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testWrongModule()
    {
        $this->dropdown->getDropdown('Toto/Toto', 'test');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid field
     * @group errors
     */
    public function testWrongField()
    {
        $this->dropdown->getDropdown('Toto', 'Toto/Toto');
    }

    public function testGetDropdown()
    {
        $data = $this->dropdown->getDropdown('Opportunities', 'sales_stage');
        $this->assertInternalType('array', $data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('Prospecting', $data);
    }

    protected function setUp()
    {
        $url = getenv('SUGARCRM_URL');
        $username = getenv('SUGARCRM_USER');
        $password = getenv('SUGARCRM_PASSWORD');

        #### START DOC
        // $url = 'http://127.0.0.1';
        // $username = 'admin';
        // $password = 'admin';

        $client = new SugarClient($url);
        $client->setUsername($username)->setPassword($password);

        $dropdown = new Dropdown($client);
        ##### END DOC

        $this->dropdown = $dropdown;
    }
}
