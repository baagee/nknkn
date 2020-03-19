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
            $redis  = Redis::getConnection();
            $key    = self::KEY_PREFIX . Config::get('app/app_name', '');
            $number = $redis->incr($key);
            return self::getBySeq($number, 1, 0);
        } else {
            // 随机生成 短时间大量生成可能有重复的 可以使用getList或者getYield批量生成
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
                yield self::getBySeq($number, $count, $i);
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                yield self::getOne(false);
            }
        }
    }

    /**
     * 生成算法
     * @param $number
     * @param $count
     * @param $seq
     * @return int
     */
    final private static function getBySeq($number, $count, $seq)
    {
        return intval(time() * 1000000 + ($number - $count + $seq + 1) * 1000000 + rand(0, 1000000));
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
                $idList[] = self::getBySeq($number, $count, $i);
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $id = self::getOne(false);
                if (isset($idList[$id])) {
                    $i--;
                    continue;
                }
                $idList[$id] = '';
            }
            $idList = array_keys($idList);
        }
        return $idList;
    }
}
