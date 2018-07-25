<?php

namespace InetProcess\SugarAPI\Tests;

use InetProcess\SugarAPI\Exception\SugarAPIException;
use InetProcess\SugarAPI\Module;
use InetProcess\SugarAPI\SugarClient;

class SugarModuleTest extends \PHPUnit_Framework_TestCase
{
    private $module;

    private static $prefix = 'PhpUnit';

    public static function deleteAllContacts()
    {
        self::deleteAll('Contacts', [
            [
                '$or' => [
                    [ 'first_name' => self::$prefix ],
                    [ 'first_name' => 'Emmanuel' ],
                ]
            ]
        ]);
    }

    public static function deleteAllNotes()
    {
        self::deleteAll('Notes', [
            [
                '$or' => [
                    [ 'name' => self::$prefix ],
                    [ 'name' => 'Test' ],
                    [ 'name' => 'Name' ],
                    [ 'name' => 'New Name' ],
                ]
            ]
        ]);
    }

    public static function deleteAllCases()
    {
        self::deleteAll('Cases', [
            [ 'name' => ['$starts' => self::$prefix ] ],
        ]);
    }

    public static function deleteAll($module, $filter)
    {
        // Search and delete all contacts with PHPUnit as First Name
        $client = new SugarClient(getenv('SUGARCRM_URL'));
        $client = $client->setUsername(getenv('SUGARCRM_USER'))
                        ->setPassword(getenv('SUGARCRM_PASSWORD'));

        $params = http_build_query([
            'filter' => $filter,
            'max_num' => 100,
        ]);
        $res = $client->get("/$module?" .$params);
        foreach ($res['records'] as $record) {
            $client->delete("/$module/" . $record['id']);
        }
    }

    public static function setUpBeforeClass()
    {
        self::deleteAllContacts();
        self::deleteAllNotes();
    }

    public static function tearDownAfterClass()
    {
        self::deleteAllContacts();
        self::deleteAllNotes();
    }

    /**
     * @expectedException InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessageRegExp /.+422.+/
     * @group errors
     */
    public function testCountBadFilter()
    {
        $this->module->count('/KBDocuments', [['first_name' => 'Test']]);
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find a route
     * @group errors
     */
    public function testCountBadModule()
    {
        $this->module->count('/Wrong', [['first_name' => 'Test']]);
    }

    public function testCountNoRecords()
    {
        $this->countContacts([['first_name' => 'PhpUnit']], 0);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testCountWrongModuleName()
    {
        $this->module->count('Toto/Toto', []);
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find a route
     * @group errors
     */
    public function testCreateBadModule()
    {
        $this->module->create('/Wrong', [['first_name' => 'Test']]);
    }

    public function testCreateOneAndCount()
    {
        self::deleteAllContacts();

        $data = ['first_name' => 'PhpUnit', 'last_name' => 'Test'];
        $contact = $this->createContactAndTest($data);
        $this->countContacts([['first_name' => 'PhpUnit']], 1);
        $this->getContactAndTest($contact['id'], $data);
        $this->deleteContactAndTest($contact['id']);
    }

    public function testCreateOneAndUpdate()
    {
        self::deleteAllContacts();

        $data = ['first_name' => 'PhpUnit', 'last_name' => 'Test'];
        $contact = $this->createContactAndTest($data);
        $this->getContactAndTest($contact['id'], $data);

        $changedData = ['last_name' => null];
        $this->updateContactAndTest($contact['id'], $changedData);
        $this->getContactAndTest($contact['id'], array_merge($data, $changedData));

        $this->deleteContactAndTest($contact['id']);
    }

    public function testCreateTenAndCount()
    {
        self::deleteAllContacts();
        for ($i = 1; $i <= 10; $i++) {
            $this->createContactAndTest(['first_name' => 'PhpUnit', 'last_name' => 'Test']);
        }
        $this->countContacts([['first_name' => 'PhpUnit']], 10);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testCreateWrongModuleName()
    {
        $this->module->create('Toto/Toto', []);
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find record: test in module: Contacts
     * @group errors
     */
    public function testDeleteBadID()
    {
        $this->module->delete('/Contacts', 'test');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testDeleteWrongModuleName()
    {
        $this->module->delete('Toto/Toto', '');
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find a route
     * @group errors
     */
    public function testRetrieveBadModule()
    {
        $this->module->retrieve('/Wrong');
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find a route
     * @group errors
     */
    public function testRetrieveBadModuleWithID()
    {
        $this->module->retrieve('/Wrong', 'test');
    }

    public function testRetrieveEverything()
    {
        self::deleteAllContacts();

        $totalContacts = $this->module->count('Contacts', []);

        $contacts = $this->module->retrieve('Contacts', null, 0, $totalContacts + 100);
        $this->assertNotEmpty($contacts);
        $this->assertInternalType('array', $contacts);
        $this->assertArrayHasKey('records', $contacts);

        $this->assertCount($totalContacts, $contacts['records']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testRetrieveWrongModuleName()
    {
        $this->module->retrieve('Toto/Toto', '');
    }

    /**
     * @expectedException InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessageRegExp /.+422.+/
     * @group errors
     */
    public function testSearchBadFilter()
    {
        $this->module->search('/KBDocuments', [['first_name' => 'Test']]);
    }

    public function testSearchFindsNothing()
    {
        self::deleteAllContacts();

        $contacts = $this->module->search('Contacts', [['first_name' => 'PhpUnit']]);
        $this->assertInternalType('array', $contacts);
        $this->assertArrayHasKey('records', $contacts);
        $this->assertEmpty($contacts['records']);
    }

    public function testSearchFindsOne()
    {
        self::deleteAllContacts();

        $data = ['first_name' => 'PhpUnit', 'last_name' => 'Test'];
        $contact = $this->createContactAndTest($data);

        $contacts = $this->module->search('Contacts', [['first_name' => 'PhpUnit']]);
        $this->assertInternalType('array', $contacts);
        $this->assertArrayHasKey('records', $contacts);
        $this->assertCount(1, $contacts['records']);

        $this->assertArrayHasKey('id', $contacts['records'][0]);
        $this->assertArrayHasKey('first_name', $contacts['records'][0]);
        $this->assertArrayHasKey('last_name', $contacts['records'][0]);

        $this->assertEquals($contact['id'], $contacts['records'][0]['id']);
        $this->assertEquals($contact['first_name'], $contacts['records'][0]['first_name']);
    }

    public function testSearchFindsOneLimitFields()
    {
        self::deleteAllContacts();

        $data = ['first_name' => 'PhpUnit', 'last_name' => 'Test'];
        $contact = $this->createContactAndTest($data);

        $contacts = $this->module->search('Contacts', [['first_name' => 'PhpUnit']], ['first_name']);
        $this->assertInternalType('array', $contacts);
        $this->assertArrayHasKey('records', $contacts);
        $this->assertCount(1, $contacts['records']);

        $this->assertArrayHasKey('id', $contacts['records'][0]);
        $this->assertArrayHasKey('first_name', $contacts['records'][0]);
        $this->assertArrayNotHasKey('last_name', $contacts['records'][0]);

        $this->assertEquals($contact['id'], $contacts['records'][0]['id']);
        $this->assertEquals($contact['first_name'], $contacts['records'][0]['first_name']);
    }

    public function testSearchFindsTenOrderBy()
    {
        self::deleteAllContacts();

        for ($i = 0; $i < 10; $i++) {
            $data = ['first_name' => 'PhpUnit', 'last_name' => 'Test ' . $i];
            $this->createContactAndTest($data);
        }

        $contacts = $this->module->search('Contacts', [['first_name' => 'PhpUnit']], [], 0, 10, 'last_name');
        $this->assertInternalType('array', $contacts);
        $this->assertArrayHasKey('records', $contacts);
        $this->assertCount(10, $contacts['records']);

        for ($i = 0; $i < 10; $i++) {
            $this->assertArrayHasKey('id', $contacts['records'][$i]);
            $this->assertArrayHasKey('last_name', $contacts['records'][$i]);

            $this->assertEquals('Test ' . $i, $contacts['records'][$i]['last_name']);
        }
    }

    public function testCountForDoc()
    {
        self::deleteAllNotes();
        $this->createNoteAndTest(['name' => 'Test']);
        $module = $this->module;

        ### START DOC
        $numNotes = $module->count('Notes', [['name' => 'Test']]);

        // echo "$numNotes in SugarCRM with name = Test";
        ### END DOC

        $this->assertEquals(1, $numNotes);
    }

    public function testSearchForDoc()
    {
        self::deleteAllNotes();
        $this->createNoteAndTest(['name' => 'Test']);
        $module = $this->module;

        ### START DOC
        $notes = $module->search('Notes', [['name' => 'Test']], [], 0, 10, 'name');

        if (!empty($notes['records'])) {
            // echo $notes['records'][0]['name']; // Displays 'Test'
        }
        ### END DOC

        $this->assertNotEmpty($notes['records']);
        $this->assertEquals('Test', $notes['records'][0]['name']);
    }

    public function testDeleteForDoc()
    {
        $note = $this->createNoteAndTest(['name' => 'Test']);
        $module = $this->module;
        $noteId = $note['id'];

        ### START DOC ALL NOTES
        // $noteId = '123456-abcdef-78910';
        $module->delete('Notes', $noteId);
        ### END DOC
    }

    public function testRetrieveForDoc()
    {
        $note = $this->createNoteAndTest(['name' => 'Test']);
        $module = $this->module;
        $noteId = $note['id'];

        ### START DOC ALL NOTES
        $notes = $module->retrieve('Notes');

        if (!empty($notes['records'])) {
            // echo $notes['records'][0]['name']; // Displays the name of the note
        }
        ### END DOC
        $this->assertNotEmpty($notes['records']);
        $this->assertEquals('Test', $notes['records'][0]['name']);

        ### START DOC ONE NOTE
        // $noteId = '123456-abcdef-78910';
        $note = $module->retrieve('Notes', $noteId);

        if (!empty($note)) {
            // echo $note['name']; // Displays the name of the note
        }
        ### END DOC
        $this->assertNotEmpty($note);
        $this->assertEquals('Test', $note['name']);
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find a route
     * @group errors
     */
    public function testSearchWrongModuleName()
    {
        $this->module->search('Toto', []);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testSearchWrongModuleParameter()
    {
        $this->module->search('Toto/Toto', []);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage test/test/test is not a valid id
     * @group errors
     */
    public function testUpdateBadID()
    {
        $this->module->update('/Contacts', 'test/test/test', []);
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Could not find record: test in module: Contacts
     * @group errors
     */
    public function testUpdateRecordDoesNotExist()
    {
        $this->module->update('/Contacts', 'test', ['first_name' => 'toto']);
    }

    public function testCreateForDOC()
    {
        $module = $this->module;

        #### START DOC
        $data = $module->create('Notes', ['name' => 'Name']);

        // echo $data['note']; // Displays New Name
        #### END DOC
        $this->assertEquals('Name', $data['name']);
    }

    public function testUpdateForDOC()
    {
        $note = $this->createNoteAndTest(['name' => 'PhpUnit']);
        $module = $this->module;
        $noteId = $note['id'];

        #### START DOC
        // $noteId = '123456-abcdef-78910';
        $data = $module->update('Notes', $noteId, ['name' => 'New Name']);

        // echo $data['note']; // Displays New Name
        #### END DOC
        $this->assertEquals('New Name', $data['name']);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testUpdateWrongModuleName()
    {
        $this->module->update('Toto/Toto', '', []);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid module
     * @group errors
     */
    public function testUploadWrongModuleName()
    {
        $this->module->upload('Toto/Toto', '', '', '', '');
    }


    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toto/Toto is not a valid id
     * @group errors
     */
    public function testUploadWrongId()
    {
        $this->module->upload('Toto', 'Toto/Toto', '', '', '');
    }

    /**
     * @expectedException \InetProcess\SugarAPI\Exception\SugarAPIException
     * @expectedExceptionMessage Unexpected field type:
     * @group errors
     */
    public function testUploadToANoteBadFilename()
    {
        self::deleteAllNotes();

        $note = $this->createNoteAndTest(['name' => 'PhpUnit']);

        $tmpfname = tempnam('/tmp', 'phpunit');
        file_put_contents($tmpfname, 'coucou');
        $this->assertFileExists($tmpfname);

        $this->module->upload('Notes', $note['id'], 'wrongField', $tmpfname, 'My File.txt');
    }

    public function testUploadToANote()
    {
        self::deleteAllNotes();

        $note = $this->createNoteAndTest(['name' => 'PhpUnit']);

        $tmpfname = tempnam('/tmp', 'phpunit');
        file_put_contents($tmpfname, 'coucou');
        $this->assertFileExists($tmpfname);

        $uploadedFile = $this->module->upload('Notes', $note['id'], 'filename', $tmpfname, 'My File.txt');
        $this->assertInternalType('array', $uploadedFile);
        $this->assertArrayHasKey('filename', $uploadedFile);
        $this->assertArrayHasKey('name', $uploadedFile['filename']);
        $this->assertEquals('My File.txt', $uploadedFile['filename']['name']);

        // Download the file
        $fileContent = $this->module->download('Notes', $note['id'], 'filename');
        $this->assertEquals('coucou', $fileContent);

        // Download now to a file
        $tmpfname = tempnam('/tmp', 'phpunit');
        $this->assertEmpty(file_get_contents($tmpfname));
        $this->module->download('Notes', $note['id'], 'filename', $tmpfname);
        $this->assertFileExists($tmpfname);
        $this->assertEquals('coucou', file_get_contents($tmpfname));
    }

    public function testDownloadForDOC()
    {
        $note = $this->createNoteAndTest(['name' => 'PhpUnit']);
        $localFile = tempnam('/tmp', 'phpunit');
        file_put_contents($localFile, 'coucou');
        $this->assertFileExists($localFile);
        $module = $this->module;
        $noteId = $note['id'];
        $module->upload('Notes', $noteId, 'filename', $localFile, 'My File.txt');
        file_put_contents($targetFile = '/tmp/file123456', '');

        #### START DOC
        // $noteId = '123456-abcdef-78910';
        $targetFile = '/tmp/file123456';
        $module->download('Notes', $noteId, 'filename', $targetFile);
        #### END DOC

        $this->assertEquals('coucou', file_get_contents($targetFile));
    }

    public function testUploadForDOC()
    {
        $note = $this->createNoteAndTest(['name' => 'PhpUnit']);
        $localFile = tempnam('/tmp', 'phpunit');
        file_put_contents($localFile, 'coucou');
        $this->assertFileExists($localFile);
        $module = $this->module;
        $noteId = $note['id'];

        #### START DOC
        // $noteId = '123456-abcdef-78910';
        // $localFile = '/tmp/file123456';
        $uploadedFile = $module->upload('Notes', $noteId, 'filename', $localFile, 'My File.txt');

        // echo $uploadedFile['filename']['name']; // Displays "My File.txt"
        #### END DOC
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

        $module = new Module($client);
        ##### END DOC

        $this->module = $module;
    }

    /**
     * @param $filters
     * @param $expected
     */
    private function countContacts($filters, $expected)
    {
        $total = $this->module->count('Contacts', $filters);
        $this->assertEquals($expected, $total);
    }

    /**
     * @param $data
     * @return mixed
     */
    private function createContactAndTest($data)
    {
        return $this->createBeanAndTest('Contacts', $data);
    }

    private function createBeanAndTest($module, $data)
    {
        $bean = $this->module->create($module, $data);
        return $this->validateBean($bean, $data);
    }

    private function validateBean($bean, $data)
    {
        $this->assertNotEmpty($bean);
        $this->assertInternalType('array', $bean);
        $this->assertArrayHasKey('id', $bean);
        $this->assertNotEmpty($bean['id']);
        foreach ($data as $field => $value) {
            $this->assertArrayHasKey($field, $bean);
            $this->assertEquals($bean[$field], $value);
        }
        return $bean;
    }

    private function createNoteAndTest($data)
    {
        $note = $this->module->create('Notes', $data);
        return $this->validateBean($note, $data);
    }


    /**
     * @param $record
     */
    private function deleteContactAndTest($record)
    {
        $contact = $this->module->delete('Contacts', $record);

        $this->assertNotEmpty($contact);
        $this->assertInternalType('array', $contact);
        $this->assertArrayHasKey('id', $contact);
        $this->assertArrayNotHasKey('first_name', $contact);
    }

    /**
     * @param $record
     * @param array $data
     * @return mixed
     */
    private function getContactAndTest($record, array $data)
    {
        $contact = $this->module->retrieve('Contacts', $record);

        return $this->validateBean($contact, $data);
    }

    /**
     * @param $record
     * @param array $data
     * @return mixed
     */
    private function updateContactAndTest($record, array $data)
    {
        $contact = $this->module->update('Contacts', $record, $data);

        return $this->validateBean($contact, $data);
    }

    /**
     * @group related
     */
    public function testUpdateRelatedLinks()
    {
        $contact = $this->createBeanAndTest('Contacts', [
            'first_name' => 'PHPUnit',
            'last_name' => 'test_rel',
        ]);
        for ($i=0; $i<10; $i++) {
            $case = $this->createBeanAndTest('Cases', [
                'name' => self::$prefix." Test rel $i",
            ]);
            $cases[$case['id']] = $case;
        }

        $this->updateRelatedRecordsAndTest('Contacts', $contact['id'], 'cases', array_keys($cases), [
            'linked_records' => array_keys($cases),
            'unlinked_records' => []
        ]);

        $new_cases = array_slice($cases, 5, null, true);
        for ($i=10; $i<15; $i++) {
            $case = $this->createBeanAndTest('Cases', [
                'name' => self::$prefix." Test rel $i",
            ]);
            $new_cases[$case['id']] = $case;
        }

        $expected = [
            'linked_records' => array_diff(array_keys($new_cases), array_keys($cases)),
            'unlinked_records' => array_diff(array_keys($cases), array_keys($new_cases)),
        ];

        $this->updateRelatedRecordsAndTest('Contacts', $contact['id'], 'cases', array_keys($new_cases), $expected);
        self::deleteAllCases();
    }

    private function updateRelatedRecordsAndTest($module, $id, $rel, $related_ids, $expected)
    {
        $res = $this->module->updateRelatedLinks($module, $id, $rel, $related_ids);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('linked_records', $res);
        $this->assertArrayHasKey('unlinked_records', $res);
        $this->assertEqualsArrayValues($expected['linked_records'], $res['linked_records']);
        $this->assertEqualsArrayValues($expected['unlinked_records'], $res['unlinked_records']);

        $actual_related_ids = $this->module->getAll("$module/$id/link/$rel");
        $this->assertInternalType('array', $actual_related_ids);
        $this->assertEqualsArrayValues($related_ids, $actual_related_ids);
    }

    private function assertEqualsArrayValues($expected, $actual)
    {
        $expected_values = array_values($expected);
        $actual_values = array_values($actual);
        sort($expected_values);
        sort($actual_values);
        $this->assertEquals($expected_values, $actual_values);
    }

    /**
     * @group related
     */
    public function testUpdateRelatedLinksApiError()
    {
        $contact = $this->createBeanAndTest('Contacts', [
            'first_name' => self::$prefix,
            'last_name' => 'test_rel',
        ]);
        $res = $this->module->updateRelatedLinks('Contacts', $contact['id'], 'cases', ['foobar']);
        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('linked_records', $res);
        $this->assertArrayHasKey('unlinked_records', $res);
        $this->assertEmpty($res['linked_records']);
        $this->assertEmpty($res['unlinked_records']);
        $this->assertArrayHasKey('errors', $res);
        $this->assertNotEmpty($res['errors']);
    }
}
