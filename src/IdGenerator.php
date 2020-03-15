<?php
/**
 * Desc: ID生成器
 * User: baagee
 * Date: 2019/4/10
 * Time: 下午7:18
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;

/**
 * Class IdGenerator
 * @package BaAGee\NkNkn
 */
final class IdGenerator
{
    /**
     * redis key前缀
     */
    private const KEY_PREFIX = 'COMMON:ID:';

    /**
     * @param bool $useRedis 是否使用redis生成
     * @return int
     * @throws \Exception
     */
    final public static function getOne(bool $useRedis = true)
    {
        if ($useRedis) {
            $redis = Redis::getConnection();
            $key   = self::KEY_PREFIX . Config::get('app/app_name', '');
            return intval(time() * 10000 + $redis->incr($key) * 10000 + rand(0, 10000));
        } else {
            return intval(microtime(true) * 1000000) + mt_rand(1000000, 9999999);
        }
    }

    /**
     * @param int  $count    个数
     * @param bool $useRedis 是否使用redis生成
     * @return \Generator
     * @throws \Exception
     */
    final public static function getYield(int $count, bool $useRedis = true)
    {
        if ($useRedis) {
            $redis  = Redis::getConnection();
            $key    = self::KEY_PREFIX . Config::get('app/app_name', '');
            $number = $redis->incrby($key, $count);
            for ($i = 0; $i < $count; $i++) {
                yield intval(time() * 10000 + ($number - $count + $i + 1) * 10000 + rand(0, 10000));
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                yield intval(microtime(true) * 1000000) + mt_rand(1000000, 9999999);
            }
        }
    }

    /**
     * @param int  $count    个数
     * @param bool $useRedis 是否使用redis生成
     * @return array
     * @throws \Exception
     */
    final public static function getList(int $count, bool $useRedis = true)
    {
        $idList = [];
        if ($useRedis) {
            $redis  = Redis::getConnection();
            $key    = self::KEY_PREFIX . Config::get('app/app_name', '');
            $number = $redis->incrby($key, $count);
            for ($i = 0; $i < $count; $i++) {
                $idList[] = intval(time() * 10000 + ($number - $count + $i + 1) * 10000 + rand(0, 10000));
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $idList[] = intval(microtime(true) * 1000000) + mt_rand(1000000, 9999999);
            }
        }
        return $idList;
    }
}
