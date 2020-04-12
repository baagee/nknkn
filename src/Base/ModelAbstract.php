<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019-08-25
 * Time: 01:04
 */

namespace BaAGee\NkNkn\Base;

use BaAGee\MySQL\DBConfig;
use BaAGee\MySQL\SimpleTable;

/**
 * Class ModelAbstract
 * @package BaAGee\NkNkn\Base
 */
abstract class ModelAbstract
{
    /**
     * @var string 数据库配置名
     */
    public static $configName = DBConfig::DEFAULT;
    /**
     * @var string 表名
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
        //自动切换数据库
        static::switchTo(static::$configName);
        $this->tableObj = SimpleTable::getInstance(static::$tableName);
    }

    /**
     * 切换数据库
     * @param string $name
     * @throws \Exception
     */
    public static function switchTo(string $name)
    {
        DBConfig::switchTo($name);
        static::$configName = $name;
    }
}
