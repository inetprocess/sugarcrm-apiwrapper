# SugarCRM Api Wrapper

# Install
```bash
composer require inetprocess/sugarcrm-apiwrapper
```

# Usage
## BaseRequest
A basic class that initiate the Guzzle HTTP Client and provides the main methods for other class to do
SugarCRM API Calls. You can use it directly but it's not recommanded. You can also build your own class based on that one.

### Example Usage:
```php
<?php

require_once 'vendor/autoload.php';

use InetProcess\SugarAPI\BaseRequest;

$url = 'http://127.0.0.1';
$username = 'admin';
$password = 'admin';

$base = new BaseRequest($url);
$base->setUsername($username)->setPassword($password);

// The login can be called dynamically as the class will detect you are not logged
// But to save an API Request, call it manually
$base->login();

// Get the list of Contacts
// '200' is the expected Status Code. If it's not the right one, you'll get an Exception
$data = $base->request('/Contacts', ['OAuth-Token' => $base->getToken()], [], 'get', 200);
```

### Other useful methods
The following methods allows you to take more control of the SugarAPIWrapper:
* `getBaseUrl()` : returns the BaseURL
* `getClient()` : returns the GuzzleClient
* `getToken()` : get the token sent by SugarCRM
* `getTokenExpiration()` : get the Token Expiration sent by Sugar
* `setToken(string $token)` and `setTokenExpiration(\DateTime $date)` : must be used together to avoid a Login
* `setLogger(\Psr\Log\LoggerInterface $logger)` set a PSR Logger
* `setPlatform(string $platform)` : defines the platform (_inetprocess_ by default)


## SugarClient
An extension of `BaseRequest` with wrappers for GET, POST, PUT and DELETE. It does an autologin and sends the right headers with the token automatically.

### Init the client
```php
<?php

require_once 'vendor/autoload.php';

use InetProcess\SugarAPI\SugarClient;

$url = 'http://127.0.0.1';
$username = 'admin';
$password = 'admin';

$client = new SugarClient($url);
$client->setUsername($username)->setPassword($password);
```

### POST To any Endpoint
```php
<?php
$data = ['first_name' => 'Emmanuel', 'last_name' => 'D.'];
$contact = $client->post('/Contacts', $data);

echo $contact['last_name']; // Should display: "D."
```

### PUT To any Endpoint
After doing the POST, do the following:
```php
<?php
$data = ['first_name' => 'Emmanuel', 'last_name' => 'Dy.'];
$contact = $client->put('/Contacts/' . $contact['id'], $data);

echo $contact['last_name']; // Should display: "Dy"
```

### GET To any Endpoint
```php
<?php
$contact = $client->get('/Contacts/' . $contact['id']);

echo $contact['last_name']; // Should display: "D."
```

### DELETE To any Endpoint
```php
<?php
$contact = $client->delete('/Contacts/' . $contact['id']);
```

### Use a Bulk request to send multiple requests in a single HTTP call
#### Get a new BulkRequest object
```php
<?php
$bulk = $client->newBulkRequest();
```

#### Send multiple requests
You can use the same functions as the SugarClient class to prepare your requests
```php
<?php
$bulk->post('/Contacts', $data);
$bulk->delete('/Contacts/'.$contact['id']);
...
$responses = $bulk->send();
```

## Module
Wrappers for specific modules actions. It does an autologin and sends the right headers with the tokens automatically.

### Init the client and the module classes
```php
<?php

require_once 'vendor/autoload.php';

use InetProcess\SugarAPI\Module;
use InetProcess\SugarAPI\SugarClient;

$url = 'http://127.0.0.1';
$username = 'admin';
$password = 'admin';

$client = new SugarClient($url);
$client->setUsername($username)->setPassword($password);

$module = new Module($client);
```

### Count Records
Count Records by applying filters

* `$module` : Module Name such as _Contacts_
* `$filters` : An Array of filters as defined in [SugarCRM Doc](http://support.sugarcrm.com/Documentation/Sugar_Developer/Sugar_Developer_Guide_7.9/Integration/Web_Services/v10/Examples/Bash/How_to_Filter_a_List_of_Records/)


Example of a count of notes with _name = Test_
```php
<?php
$numNotes = $module->search('Notes', [['name' => 'Test']]);

echo "$numNotes in SugarCRM with name = Test";
```

### Search records
Search Records by applying filters (the structure of filters is the same than for Count).

* `$module` : Module Name such as _Contacts_
* `$filters` : An Array of filters as defined in [SugarCRM Doc](http://support.sugarcrm.com/Documentation/Sugar_Developer/Sugar_Developer_Guide_7.9/Integration/Web_Services/v10/Examples/Bash/How_to_Filter_a_List_of_Records/)
* `$fields` : list of fields to get back, all by default
* `$offset` : 0 by default
* `$maxNum` : 20 by default
* `$orderBy` : null by default

Example of a search that should retrieve a max of 10 notes with _name = Test_, ordered by name:
```php
<?php
$notes = $module->search('Notes', [['name' => 'Test']], [], 0, 10, 'name');

if (!empty($notes['records'])) {
    echo $notes['records'][0]['name']; // Displays 'Test'
}
```

### Retrieve one or multiple records
* `$module` : Module Name such as _Contacts_
* `$record` : Record ID, null by default
* `$offset` : 0 by default
* `$maxNum` : 20 by default

Retrieve a max of 10 records:
```php
<?php
$notes = $module->retrieve('Notes', null, 0, 10);

if (!empty($notes['records'])) {
    echo $notes['records'][0]['name']; // Displays the name of the note
}
```


Retrieve a single record (throws a SugarAPIException if it does not Exists)
```php
<?php
$noteId = '123456-abcdef-78910';
$note = $module->retrieve('Notes', $noteId);

if (!empty($note)) {
    echo $note['name']; // Displays the name of the note
}
```

### Create a record
Parameters:
* `$module` : Module Name such as _Contacts_
* `$data` : Array of fields => values

```php
<?php
$data = $module->create('Notes', ['name' => 'Name']);

echo $data['note']; // Displays New Name
```

### Update a record
Parameters:
* `$module` : Module Name such as _Contacts_
* `$record` : ID of the record
* `$data` : Array of fields => values

```php
<?php

$noteId = '123456-abcdef-78910';
$data = $module->update('Notes', $noteId, ['name' => 'New Name']);

echo $data['note']; // Displays New Name
```

### Delete a record
Parameters:
* `$module` : Module Name such as _Contacts_
* `$record` : ID of the record

```php
<?php

$noteId = '123456-abcdef-78910';
$module->delete('Notes', $noteId);
```

### Set all related ids to a record
Parameters:
* `$module`   : Module Name such as _Contacts_
* `$record`   : ID of the record
* `$linkName` : Relationship name
* `$relatedIds`: Array of ids from the related module.
  This array is the full set of related ids, it will remove existing related links if they are not sent here.

```php
<?php
$contactId = '123456-abcdef-78910';
$casesIds = ['756335-abcdef-12340', '5475626-fedba-545761'];
$res = $module->updateRelatedLinks('Contacts', $contactId, 'cases', $casesIds);
var_export($res);
/*  [
        'linked_records' => ['756335-abcdef-12340', '5475626-fedba-545761'],
        'unlinked_records' => ['234782-gfbeaf-7672'],
        'errors' => [],
    ]
*/
```

### Download a file
Parameters:
* `$module` : Module Name such as _Contacts_
* `$record` : ID of the record
* `$field` : Field Name
* `$targetFile` : Local File to write the content. **If empty, returns directly the data**
* `$originalName` : Name of the file displayed in Sugar


The following example downloads a file and put its content to the `$targetFile` file
```php
<?php
$noteId = '123456-abcdef-78910';
$targetFile = '/tmp/file123456';
$module->download('Notes', $noteId, 'filename', $targetFile);
```


### Upload a file
Parameters:
* `$module` : Module Name such as _Contacts_
* `$record` : ID of the record
* `$field` : Field Name
* `$filePath` : Local File Path
* `$originalName` : Name of the file displayed in Sugar

```php
<?php

$noteId = '123456-abcdef-78910';
$localFile = '/tmp/file123456';
$uploadedFile = $module->upload('Notes', $noteId, 'filename', $localFile, 'My File.txt');

echo $uploadedFile['filename']['name']; // Displays "My File.txt"
```


## Dropdown
Retrieves the keys / values of a dropdown, in the current user (the one defined in setUserName) language.

Parameters:
* `$module` : Module Name such as _Contacts_
* `$field` : Field for which we needs the values

```php
<?php

require_once 'vendor/autoload.php';

use InetProcess\SugarAPI\Dropdown;
use InetProcess\SugarAPI\SugarClient;

$url = 'http://127.0.0.1';
$username = 'admin';
$password = 'admin';

$client = new SugarClient($url);
$client->setUsername($username)->setPassword($password);

$dropdown = new Dropdown($client);
$salesStages = $dropdown->getDropdown('Opportunities', 'sales_stage');
```
