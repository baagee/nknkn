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
     * @return int
     * @throws \Exception
     */
    final public static function getOne()
    {
        $redis = Redis::getConnection();
        $key   = self::KEY_PREFIX . Config::get('app/app_name', '');
        return intval(time() * 10000 + $redis->incr($key) * 10000 + rand(0, 10000));
    }

    /**
     * @param int $count
     * @return \Generator
     * @throws \Exception
     */
    final public static function getYield(int $count)
    {
        $redis  = Redis::getConnection();
        $key    = self::KEY_PREFIX . Config::get('app/app_name', '');
        $number = $redis->incrby($key, $count);
        for ($i = 0; $i < $count; $i++) {
            yield intval(time() * 10000 + ($number - $count + $i + 1) * 10000 + rand(0, 10000));
        }
    }

    /**
     * @param int $count
     * @return array
     * @throws \Exception
     */
    final public static function getList(int $count)
    {
        $redis  = Redis::getConnection();
        $key    = self::KEY_PREFIX . Config::get('app/app_name', '');
        $number = $redis->incrby($key, $count);
        $idList = [];
        for ($i = 0; $i < $count; $i++) {
            $idList[] = intval(time() * 10000 + ($number - $count + $i + 1) * 10000 + rand(0, 10000));
        }
        return $idList;
    }
}
