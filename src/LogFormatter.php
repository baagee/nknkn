<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019-08-26
 * Time: 10:28
 */

namespace BaAGee\NkNkn;

use BaAGee\Config\Config;
use BaAGee\Log\Base\LogFormatter as BaseLogFormatter;

/**
 * Class LogFormatter
 * @package BaAGee\NkNkn
 */
class LogFormatter extends BaseLogFormatter
{
    /**
     * @param string $level
     * @param string $log
     * @param string $file
     * @param int    $line
     * @return false|string
     */
    protected static function getLogString($level, $log, $file, $line)
    {
        list($t1, $t2) = explode('.', microtime(true));
        $time = sprintf('%s.%s', date('Y-m-d H:i:s', $t1), $t2);
        return json_encode([
            'level'     => $level,
            'time'      => $time,
            'trace_id'  => AppEnv::get('TRACE_ID'),
            'app_name'  => Config::get('app/app_name'),
            'php_sapi'  => PHP_SAPI,
            'client_ip' => static::getClientIp(),
            'uri'       => $_SERVER["REQUEST_URI"] ?? '~',
            'cookie'    => $_COOKIE,
            'file'      => $file,
            'line'      => $line,
            'log'       => $log,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取客户端IP
     */
    protected static function getClientIp()
    {
        $ip      = 'unknown';
        $unknown = 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            // 使用透明代理、欺骗性代理的情况
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            // 没有代理、使用普通匿名代理和高匿代理的情况
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // 处理多层代理的情况
        if (strpos($ip, ',') !== false) {
            // 输出第一个IP
            $ip = reset(explode(',', $ip));
        }
        return $ip;
    }
}
