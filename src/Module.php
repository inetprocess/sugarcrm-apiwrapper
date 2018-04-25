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

class Module
{
    /**
     * @var SugarClient
     */
    protected $sugarClient;

    /**
     * @param SugarClient $sugarClient
     */
    public function __construct(SugarClient $sugarClient)
    {
        $this->sugarClient = $sugarClient;
    }

    /**
     * @param  string $module
     * @param  array  $filters
     * @return int
     */
    public function count($module, array $filters)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");

        $filters = !empty($filters) ? '?' . http_build_query(['filter' => $filters]) : '';
        try {
            $res = $this->sugarClient->get($module . '/count' . $filters, 200);
            if (!array_key_exists('record_count', $res)) {
                throw new SugarAPIException("Can't get a record_count key during a GET /{module}/count");
            }

            $total = (int) $res['record_count'];
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module);
        }

        return $total;
    }

    /**
     * @param  string  $module
     * @param  array   $data
     * @return array
     */
    public function create($module, array $data)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::notEmpty($data, "Data can't be empty");

        try {
            $data = $this->sugarClient->post($module, $data, 200);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module);
        }

        return $data;
    }

    /**
     * @param  string  $module
     * @param  string  $record
     * @return array
     */
    public function delete($module, $record)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::false(strpos($record, '/') || strpos($record, '?'), "$record is not a valid id");
        Assert::notEmpty($record, "Record ID can't be empty");

        try {
            $data = $this->sugarClient->delete($module . '/' . $record, 200);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $record);
        }

        return $data;
    }

    /**
     * @param  string  $module
     * @param  string  $record
     * @return array
     */
    public function download($module, $record, $field, $targetFile = null)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::false(strpos($record, '/') || strpos($record, '?'), "$record is not a valid id");
        Assert::false(strpos($field, '/') || strpos($field, '?'), "$field is not a valid field");
        Assert::notEmpty($record, "Record ID can't be empty");
        Assert::notEmpty($field, "Field Name can't be empty");

        $url = $module . '/' . $record . '/file/' . $field;

        try {
            $fileContent = $this->sugarClient->get($url, 200, true);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $record);
        }

        if (is_null($targetFile)) {
            return $fileContent;
        }

        Assert::writable($targetFile, "$targetFile must be writeable");
        file_put_contents($targetFile, $fileContent);
    }


    /**
     * @param  string  $module
     * @param  string  $record
     * @return array
     */
    public function retrieve($module, $record = null, $offset = 0, $maxNum = 20)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");

        $url = $module . "?max_num={$maxNum}&offset={$offset}";
        if (!is_null($record)) {
            Assert::false(strpos($record, '/') || strpos($record, '?'), "$record is not a valid id");
            $url = $module . '/' . $record;
        }

        try {
            $data = $this->sugarClient->get($url);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $record);
        }

        return $data;
    }

    /**
     * @param  string  $module
     * @param  array   $filters
     * @param  array   $fields
     * @param  int     $offset
     * @param  int     $maxNum
     * @param  string  $orderBy
     * @return array
     */
    public function search($module, array $filters, array $fields = [], $offset = 0, $maxNum = 20, $orderBy = null)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");

        $body = ['filter' => $filters, 'max_num' => $maxNum, 'offset' => $offset];
        if (!empty($fields)) {
            $body['fields'] = $fields;
        }
        if (!empty($orderBy)) {
            $body['order_by'] = $orderBy;
        }

        try {
            $data = $this->sugarClient->post($module . '/filter', $body, 200);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module);
        }

        return $data;
    }

    /**
     * @param  string  $module
     * @param  string  $record
     * @param  array   $data
     * @return array
     */
    public function update($module, $record, array $data)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::false(strpos($record, '/') || strpos($record, '?'), "$record is not a valid id");
        Assert::notEmpty($record, "Record ID can't be empty");
        Assert::notEmpty($data, "Data can't be empty");

        try {
            $data = $this->sugarClient->put("{$module}/{$record}", $data);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $record);
        }

        return $data;
    }

    /**
     * @param  string  $module
     * @param  string  $record
     * @param  string  $field
     * @param  string  $filePath
     * @param  string  $originalName
     * @return array
     */
    public function upload($module, $record, $field, $filePath, $originalName)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::false(strpos($record, '/') || strpos($record, '?'), "$record is not a valid id");

        $url = "{$module}/{$record}/file/{$field}";
        $data = [
            'field' => $field,
            'filename' => $originalName,
            'contents' => file_get_contents($filePath),
        ];

        try {
            $data = $this->sugarClient->post($url, $data, 200);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module);
        }

        return $data;
    }
    
    
    /**
     * Set the related records list exactly as it is in $linkIds.
     * By removing extra relationships from the CRM.
     * And adding missing relationships into the CRM.
     *
     * @param   string  $moduleName Module name.
     * @param   string  $recordId   Main record id.
     * @param   string  $linkName   Relationship to use.
     * @param   array   $linkIds    Ids of the records that we want to link to the main record.
     *
     * @return  array   Contains 'linked_records' & 'unlinked_records' which are both arrays.
     */
    public function updateRelatedLinks($moduleName, $recordId, $linkName, $linkIds = array())
    {
        Assert::false(strpos($moduleName, '/') || strpos($moduleName, '?'), "$moduleName is not a valid module");
        Assert::false(strpos($record, '/') || strpos($record, '?'), "$record is not a valid id");
        Assert::false(strpos($linkName, '/') || strpos($linkName, '?'), "$linkName is not a valid link name");

        $url = implode('/', array($moduleName, $recordId, 'link', $linkName));

        $contactIds = $this->getAll($url, true);
        $linksToDelete = array_diff($contactIds, $linkIds);
        $linksToPost = array_diff($linkIds, $contactIds);

        foreach ($linksToDelete as $linkId) {
            $this->sugarClient->delete($url . '/' . $linkId);
        }

        foreach ($linksToPost as $linkId) {
            $this->sugarClient->post($url . '/' . $linkId, array());
        }

        return array(
            'linked_records' => $linksToPost,
            'unlinked_records' => $linksToDelete,
        );
    }

    /**
     * All retrieving of all the records from an endpoint or their ids.
     *
     * @param   string  $endpoint   Rest API endpoint to use.
     * @param   string  $idsOnly    Whether or not we should retrieve the whole records or just their ids.
     *
     * @return All the records or their ids.
     */
    protected function getAll($endpoint, $idsOnly = true)
    {
        $nextOffset = 0;
        $contacts = array();
        do {
            $fullEndpoint = $endpoint . '?offset=' . $nextOffset;
            $page = $this->sugarClient->get($fullEndpoint);

            $records = ($idsOnly) ? array_column($page['records'], 'id') : $page['records'];
            $contacts = array_merge($contacts, $records);

            $nextOffset = $page['next_offset'];
        } while ($nextOffset > 0);

        return $contacts;
    }

    /**
     * @param \Exception $exception
     * @param string     $module
     * @param string     $record
     */
    private function handleSugarError(\Exception $exception, $module, $record = null)
    {
        if ($exception->getCode() === 404) {
            $module = ltrim($module, '/');
            $record = ltrim($record, '/');

            $idNotFound = empty($record) ? '' : " or id $record";

            throw new Exception\SugarAPIException("Module $module" . $idNotFound . ' not found');
        }

        throw $exception;
    }
}
