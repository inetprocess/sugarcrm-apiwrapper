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

class Dropdown
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
     * Get a Dropdown
     * @param  string $module
     * @param  string $field
     * @return array
     */
    public function getDropdown($module, $field)
    {
        Assert::false(strpos($module, '/') || strpos($module, '?'), "$module is not a valid module");
        Assert::false(strpos($field, '/') || strpos($field, '?'), "$field is not a valid field");

        return $this->sugarClient->get("{$module}/enum/{$field}");
    }
}
