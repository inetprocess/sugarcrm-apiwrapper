<?php

namespace InetProcess\SugarAPI;

use Webmozart\Assert\Assert;

class Module
{
    protected $sugarcrm;

    public function __construct(SugarClient $sugarcrm)
    {
        $this->sugarcrm = $sugarcrm;
    }

    public function create($module, array $data)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");

        try {
            return $this->sugarcrm->post($module, $data, 200);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module);
        }
    }

    public function update($module, $id, array $data)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::false(strpos($id, '/') || strpos($id, '?'), "$id is not a valid id");

        try {
            return $this->sugarcrm->put("{$module}/{$id}", $data);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $id);
        }
    }

    public function retrieve($module, $id = null)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        if (!is_null($id)) {
            Assert::false(strpos($id, '/') || strpos($id, '?'), "$id is not a valid id");
            $id = '/' . $id;
        }

        try {
            return $this->sugarcrm->get($module . $id);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $id);
        }
    }

    public function search($module, array $filters, $offset)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");

        try {
            return $this->sugarcrm->get($module . $id . '?filter=' . http_build_query($filters));
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $id);
        }
    }

    public function delete($module, $id)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::false(strpos($id, '/') || strpos($id, '?'), "$id is not a valid id");

        try {
            return $this->sugarcrm->delete($module . '/' . $id, 200);
        } catch (\Exception $e) {
            $this->handleSugarError($e, $module, $id);
        }
    }

    private function handleSugarError(\Exception $e, $module, $id = null)
    {
        if ($e->getCode() === 404) {
            throw new Exception\SugarAPIException("Module $module " . (is_null($id) ? '' : "or id $id ") . 'not found');
        }

        throw $e;
    }
}
