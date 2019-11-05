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

/**
 * Class Redis
 * @package BaAGee\NkNkn
 */
class Redis
{
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
            $sTime    = microtime(true);
            $redisObj = new \Redis();
            $config   = Config::get('redis');

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
                Log::error('连接redis失败：' . json_encode($config));
                throw new \Exception('连接Redis失败');
            }
            if (!empty($config['password'])) {
                $res = $redisObj->auth($config['password']);
                if ($res == false) {
                    Log::error('连接redis失败, 密码错误：' . json_encode($config));
                    throw new \Exception('连接redis失败, 密码错误');
                }
            }
            static::$redisObj = $redisObj;
            $eTime            = microtime(true);
            Log::info(sprintf('Call Redis method:%s usedTime:%sms', ($config['pconnect'] ? 'pconnect' : 'connect'),
                ($eTime - $sTime) * 1000));
            static::$selfObj = new static();
        }
        return static::$selfObj;
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::$redisObj, $name)) {
            $sTime = microtime(true);
            Log::info(sprintf('Call Redis method:%s args:%s', $name, json_encode($arguments, JSON_UNESCAPED_UNICODE)));
            $res   = call_user_func_array([static::$redisObj, $name], $arguments);
            $eTime = microtime(true);
            Log::info(sprintf('Call Redis method:%s usedTime:%sms', $name, ($eTime - $sTime) * 1000));
            return $res;
        } else {
            return false;
        }
    }
}
