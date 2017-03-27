<?php

namespace InetProcess\SugarAPI;

use Webmozart\Assert\Assert;

class ModuleClient
{
    protected $sugarcrm;

    public function __construct(SugarClient $sugarcrm)
    {
        $this->sugarcrm = $sugarcrm;
    }

    public function create($module, array $data)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid id");

        return $this->sugarcrm->post($module, $data);
    }

    public function update($module, $id, array $data)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid id");
        Assert::false(strpos($id, '/') || strpos($id, '?'), "$id is not a valid id");

        return $this->sugarcrm->put("{$module}/{$id}", $data);
    }

    public function retrieve($module, $id)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid id");
        Assert::false(strpos($id, '/') || strpos($id, '?'), "$id is not a valid id");

        return $this->sugarcrm->get($module, $id);
    }

    public function delete($module, $id)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid id");
        Assert::false(strpos($id, '/') || strpos($id, '?'), "$id is not a valid id");

        return $this->sugarcrm->delete($module, $id);
    }
}
