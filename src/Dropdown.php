<?php

namespace InetProcess\SugarAPI;

use Webmozart\Assert\Assert;

class Dropdown
{
    protected $sugarcrm;

    public function __construct(SugarClient $sugarcrm)
    {
        $this->sugarcrm = $sugarcrm;
    }

    public function getDropdown($module, $field)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid $module");
        Assert::false(strpos($field, '/') || strpos($field, '?'), "$field is not a valid $field");

        return $this->sugarcrm->get("{$module}/enum/{$field}");
    }
}
