<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019-08-25
 * Time: 01:04
 */

namespace BaAGee\NkNkn\Base;

use BaAGee\MySQL\SimpleTable;

/**
 * Class ModelAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class ModelAbstract
{
    /**
     * @var string
     */
    public static $tableName = '';
    /**
     * @var SimpleTable
     */
    protected $tableObj = null;

    /**
     * BaseModel constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->tableObj = SimpleTable::getInstance(static::$tableName);
    }
}
