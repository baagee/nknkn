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
     * 设置值 只针对当前请求有效
     * @param string $key   名字
     * @param mixed  $value 值
     */
    public static function set($key, $value)
    {
        self::$env[strtoupper($key)] = $value;
    }

    /**
     * 获取值
     * @param string $key 名字
     * @param null   $default
     * @return mixed|null
     */
    public static function get($key, $default = null)
    {
        $uppKey = strtoupper($key);
        if (isset(self::$env[$uppKey])) {
            return self::$env[$uppKey];
        } else {
            switch ($uppKey) {
                case 'ROOT_PATH':
                    $rootPath = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..']));
                    self::set('ROOT_PATH', $rootPath);
                    return $rootPath;
                    break;
                case 'RUNTIME_PATH':
                    $runtimePath = implode(DIRECTORY_SEPARATOR, [self::get('ROOT_PATH'), 'runtime']);
                    self::set('RUNTIME_PATH', $runtimePath);
                    return $runtimePath;
                    break;
                case 'APP_PATH':
                    $appPath = implode(DIRECTORY_SEPARATOR, [self::get('ROOT_PATH'), 'app']);
                    self::set('APP_PATH', $appPath);
                    return $appPath;
                    break;
                case 'CONFIG_PATH':
                    $configPath = implode(DIRECTORY_SEPARATOR, [self::get('ROOT_PATH'), 'config']);
                    self::set('CONFIG_PATH', $configPath);
                    return $configPath;
                    break;
                default:
                    return $default;
            }
        }
    }
}
