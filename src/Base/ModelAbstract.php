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
     * @var array
     */
    protected static $selfMap = [];

    /**
     * @var string
     */
    protected static $configName = DBConfig::DEFAULT;

    /**
     * @var string 表名
     */
    protected static $tableName = '';
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
        DBConfig::switchTo(static::$configName);
        Log::info(sprintf("%s切换数据库到%s", static::$tableName, static::$configName));
        //自动切换数据库
        $this->tableObj = SimpleTable::getInstance(static::$tableName);
    }

    /**
     * 获取对象
     * @return static
     * @throws \Exception
     */
    final public static function getInstance()
    {
        if (!isset(static::$selfMap[static::$tableName])) {
            $self = new static();
            static::$selfMap[static::$tableName] = $self;
        } else {
            DBConfig::switchTo(static::$configName);
            Log::info(sprintf("%s切换数据库到%s", static::$tableName, static::$configName));
        }
        return static::$selfMap[static::$tableName];
    }

    /**
     * 切换数据库配置
     * @param string $configName
     * @return $this
     * @throws \Exception
     */
    final public function switchTo($configName = DBConfig::DEFAULT)
    {
        DBConfig::switchTo($configName);
        Log::info(sprintf("%s切换数据库到%s", static::$tableName, $configName));
        return $this;
    }
}
