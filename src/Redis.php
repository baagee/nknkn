<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019-08-24
 * Time: 14:01
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;
use BaAGee\Log\Log;
use BaAGee\NkNkn\Base\TimerTrait;

/**
 * Class Redis
 * @package BaAGee\NkNkn
 */
class Redis
{
    use TimerTrait;
    /**
     * @var null
     */
    protected static $redisObj = null;

    /**
     * @var \Redis
     */
    protected static $selfObj = null;

    /**
     * RedisConnection constructor.
     */
    private function __construct()
    {
    }

    /**
     *
     */
    private function __clone()
    {
    }

    /**
     * @return \Redis
     * @throws \Exception
     */
    public static function getConnection()
    {
        if (static::$redisObj == null) {
            $config = Config::get('redis', []);
            if (empty($config)) {
                throw new \Exception("没找到redis配置文件");
            }
            list(, $time) = self::executeTime(function ($config) {
                $redisObj = new \Redis();

                $res = false;
                for ($i = 0; $i <= intval($config['retryTimes'] ?? 0); $i++) {
                    if (isset($config['pconnect']) && $config['pconnect'] == true) {
                        $res = $redisObj->pconnect($config['host'], $config['port'], intval($config['timeout'] ?? 1));
                    } else {
                        $res = $redisObj->connect($config['host'], $config['port'], intval($config['timeout'] ?? 1));
                    }
                    if ($res == true) {
                        break;
                    }
                }
                if ($res == false) {
                    Log::emergency('Connect redis failed：' . json_encode($config));
                    throw new \Exception('Connect redis failed');
                }
                if (!empty($config['password'])) {
                    $res = $redisObj->auth($config['password']);
                    if ($res == false) {
                        Log::emergency('Connect redis failed, invalid password：' . json_encode($config));
                        throw new \Exception('连接redis失败, 密码错误');
                    }
                }
                static::$redisObj = $redisObj;
            }, 0, $config);
            Log::info(sprintf('Call Redis method:%s time:%sms', ($config['pconnect'] ? 'pconnect' : 'connect'), $time));
            static::$selfObj = new static();
        }
        return static::$selfObj;
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool|mixed
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::$redisObj, $name)) {
            Log::info(sprintf('Call Redis method:%s args:%s', $name, json_encode($arguments, JSON_UNESCAPED_UNICODE)));
            list($res, $time) = self::executeTime([static::$redisObj, $name], 1, $arguments);
            Log::info(sprintf('Call Redis method:%s time:%sms', $name, $time));
            return $res;
        } else {
            return false;
        }
    }
}
