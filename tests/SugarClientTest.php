<?php

namespace InetProcess\SugarAPI\Tests;

use InetProcess\SugarAPI\Exception\SugarAPIException;
use InetProcess\SugarAPI\SugarClient;

class SugarClientTest extends \PHPUnit_Framework_TestCase
{
    private $client;

    public function testGet()
    {
        $this->assertInstanceOf(
            'InetProcess\SugarAPI\SugarClient',
            $this->client->setUsername(getenv('SUGARCRM_USER'))
        );
        $this->assertInstanceOf(
            'InetProcess\SugarAPI\SugarClient',
            $this->client->setPassword(getenv('SUGARCRM_PASSWORD'))
        );

        $data = $this->client->get('/Contacts');
        $this->assertNotEmpty($data);
        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('records', $data);
    }

    /**
     * @expectedException InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find a route
     * @group errors
     */
    public function testGetNotFound()
    {
        $this->client->get('/Contactss');
    }

    public function testPostThenDelete()
    {
        $data = ['first_name' => 'PhpUnit', 'last_name' => 'Test'];
        $contact = $this->createContactAndTest($data);
        $this->getContactAndTest($contact['id'], $data);
        $this->deleteContactAndTest($contact['id']);

        try {
            $this->client->get('/Contacts' . $contact['id']);
            $this->assertTrue(false, 'Trying to get the contact did not throw an exception');
        } catch (SugarAPIException $e) {
            $this->assertContains('Could not find a route', $e->getMessage());
        }
    }

    public function testPostThenPutThenDelete()
    {
        $data = ['first_name' => 'PhpUnit', 'last_name' => 'Test'];
        $contact = $this->createContactAndTest($data);
        $this->getContactAndTest($contact['id'], $data);

        $changedData = ['last_name' => null];
        $this->updateContactAndTest($contact['id'], $changedData);
        $this->getContactAndTest($contact['id'], array_merge($data, $changedData));

        $this->deleteContactAndTest($contact['id']);
    }

    public function testPOSTInDoc()
    {
        $client = $this->client;
        #### START DOC EXAMPLE
        $data = ['first_name' => 'Emmanuel', 'last_name' => 'D.'];
        $contact = $client->post('/Contacts', $data);

        // echo $contact['last_name']; // Should display: "D."
        #### END DOC

        $this->getContactAndTest($contact['id'], $data);
    }

    public function testGETInDoc()
    {
        $data = ['first_name' => 'Emmanuel', 'last_name' => 'D.'];
        $contact = $this->createContactAndTest($data);

        $client = $this->client;
        #### START DOC EXAMPLE
        $contact = $client->get('/Contacts/' . $contact['id']);

        // echo $contact['last_name']; // Should display: "D."
        #### END DOC

        $this->getContactAndTest($contact['id'], $data);
    }

    public function testPUTInDoc()
    {
        $data = ['first_name' => 'Emmanuel', 'last_name' => 'D.'];
        $contact = $this->createContactAndTest($data);

        $client = $this->client;
        #### START DOC EXAMPLE
        $data = ['first_name' => 'Emmanuel', 'last_name' => 'Dy.'];
        $contact = $client->put('/Contacts/' . $contact['id'], $data);

        // echo $contact['last_name']; // Should display: "Dy"
        #### END DOC

        $this->getContactAndTest($contact['id'], $data);
    }

    public function testDELETEInDoc()
    {
        $data = ['first_name' => 'Emmanuel', 'last_name' => 'D.'];
        $contact = $this->createContactAndTest($data);

        $client = $this->client;
        #### START DOC EXAMPLE
        $contact = $client->delete('/Contacts/' . $contact['id']);
        #### END DOC
    }

    public static function tearDownAfterClass()
    {
        // Search and delete all contacts with PHPUnit as First Name
        $client = new SugarClient(getenv('SUGARCRM_URL'));
        $contacts = $client->setUsername(getenv('SUGARCRM_USER'))
                           ->setPassword(getenv('SUGARCRM_PASSWORD'))
                           ->get("/Contacts?filter[][first_name]=PhpUnit");

        if (empty($contacts['records'])) {
            return;
        }

        foreach ($contacts['records'] as $contact) {
            $client->delete('/Contacts/' . $contact['id']);
        }
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
        ##### END DOC


        $this->client = $client;
    }

    private function createContactAndTest(array $data)
    {
        $contact = $this->client->post('/Contacts', $data);
        $this->assertNotEmpty($contact);
        $this->assertInternalType('array', $contact);
        $this->assertArrayHasKey('id', $contact);
        $this->assertArrayHasKey('first_name', $contact);
        $this->assertArrayHasKey('last_name', $contact);
        $this->assertNotEmpty($contact['id']);
        $this->assertEquals($data['first_name'], $contact['first_name']);
        $this->assertEquals($data['last_name'], $contact['last_name']);

        return $contact;
    }

    private function getContactAndTest($record, array $data)
    {
        $contact = $this->client->get('/Contacts/' . $record);
        $this->assertNotEmpty($contact);
        $this->assertInternalType('array', $contact);
        $this->assertArrayHasKey('id', $contact);
        $this->assertArrayHasKey('first_name', $contact);
        $this->assertArrayHasKey('last_name', $contact);
        $this->assertNotEmpty($contact['id']);
        $this->assertEquals($data['first_name'], $contact['first_name']);
        $this->assertEquals($data['last_name'], $contact['last_name']);

        return $contact;
    }

    private function updateContactAndTest($record, array $data)
    {
        $contact = $this->client->put('/Contacts/' . $record, $data);
        $this->assertNotEmpty($contact);
        $this->assertInternalType('array', $contact);
        $this->assertArrayHasKey('id', $contact);
        $this->assertArrayHasKey('first_name', $contact);
        $this->assertArrayHasKey('last_name', $contact);
        $this->assertNotEmpty($contact['id']);
        $this->assertEmpty($contact['last_name']);

        return $contact;
    }

    private function deleteContactAndTest($record)
    {
        $contact = $this->client->delete('/Contacts/' . $record);
        $this->assertNotEmpty($contact);
        $this->assertInternalType('array', $contact);
        $this->assertArrayHasKey('id', $contact);
        $this->assertArrayNotHasKey('first_name', $contact);
    }
}
