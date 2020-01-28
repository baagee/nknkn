<?php
/**
 * Desc: 程序执行计时器
 * User: baagee
 * Date: 2019/11/6
 * Time: 下午2:57
 */

namespace BaAGee\NkNkn\Base;

/**
 * Trait TimerTrait
 * @package BaAGee\NkNkn\Base
 */
trait TimerTrait
{
    /**
     * 计时执行
     * @param callable $func       运行函数
     * @param int      $retryTimes 重试次数
     * @param mixed    ...$args    函数参数
     * @return array [result,cost执行时间]
     * @throws \Exception
     */
    protected static function executeTime(callable $func, $retryTimes = 0, ...$args)
    {
        $sTime = microtime(true);

        $result = null;
        $_i     = 0;
        while (true) {
            try {
                $result = call_user_func_array($func, $args);
                break;
            } catch (\Exception $e) {
                if ($_i >= $retryTimes) {
                    throw $e;
                } else {
                    $_i++;
                }
            }
        }

        $eTime = microtime(true);
        $cost  = number_format(($eTime - $sTime) * 1000, 3, '.', '');
        return [$result, $cost];
    }
}
