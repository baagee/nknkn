<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019-08-25
 * Time: 01:04
 */

namespace BaAGee\NkNkn\Base;

use BaAGee\Log\Log;
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
        $name = static::$configName ?? self::$configName;
        DBConfig::switchTo($name);
        static::$configName = $name;
        Log::info(static::$tableName . ' 切换数据库到：' . $name);
        $this->tableObj = SimpleTable::getInstance(static::$tableName);
    }

    /**
     * 切换当前表的数据库 需要重新new Model才生效
     * @param $name
     * @return static
     * @throws \Exception
     */
    public static function switchTo(string $name)
    {
        static::$configName = $name;
        return new static();
    }
}
