<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/9/21
 * Time: 23:16
 */

namespace BaAGee\NkNkn;

/**
 * Class AppEnv
 * @package BaAGee\NkNkn
 */
final class AppEnv
{
    /**
     * @var array
     */
    private static $env = [];

    /**
     * @param $isDebug
     */
    public static function init($isDebug)
    {
        self::set('IS_DEBUG', $isDebug);
        self::set('ROOT_PATH', realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..'])));
        self::set('RUNTIME_PATH', implode(DIRECTORY_SEPARATOR, [self::get('ROOT_PATH'), 'runtime']));
        self::set('APP_PATH', implode(DIRECTORY_SEPARATOR, [self::get('ROOT_PATH'), 'app']));
        self::set('CONFIG_PATH', implode(DIRECTORY_SEPARATOR, [self::get('ROOT_PATH'), 'config']));
        self::set('LOG_ID', (microtime(true) * 10000) . mt_rand(1000, 9999));
    }

    /**
     * @param $key
     * @param $value
     */
    public static function set($key, $value)
    {
        self::$env[strtoupper($key)] = $value;
    }

    /**
     * @param      $key
     * @param null $default
     * @return mixed|null
     */
    public static function get($key, $default = null)
    {
        return self::$env[strtoupper($key)] ?? $default;
    }
}
