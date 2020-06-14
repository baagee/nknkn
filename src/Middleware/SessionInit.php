<?php
/**
 * Desc: Session初始化
 * User: baagee
 * Date: 2019/9/23
 * Time: 10:29
 */

namespace BaAGee\NkNkn\Middleware;

use BaAGee\Config\Config;
use BaAGee\Log\Log;
use BaAGee\Session\Handler\Redis;
use BaAGee\Session\Session;
use BaAGee\NkNkn\Base\MiddlewareAbstract;

/**
 * Class SessionInit
 * @package BaAGee\NkNkn\Middleware
 */
class SessionInit extends MiddlewareAbstract
{
    /**
     * @param \Closure $next
     * @param          $data
     * @return mixed
     * @throws \Exception
     */
    protected function handler(\Closure $next, $data)
    {
        $sessionConfig = Config::get('session', []);
        if (!empty($sessionConfig)) {
            if ($sessionConfig['handler'] == Redis::class && !isset($sessionConfig['host'])) {
                $redisConfig = Config::get('redis', []);
                if (empty($redisConfig)) {
                    throw new \Exception("没找到redis配置文件");
                }
                $redisConfig['persistent'] = $redisConfig['pconnect'] ?? false;
                unset($redisConfig['pconnect']);
                $sessionConfig = array_merge($sessionConfig, $redisConfig);
            }
            Session::init($sessionConfig);
            Log::info('Session init');
        }
        return $next($data);
    }
}
