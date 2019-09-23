<?php
/**
 * Desc:
 * User: baagee
 * Date: 2019/9/23
 * Time: 10:29
 */

namespace BaAGee\NkNkn\Middleware;

use BaAGee\Config\Config;
use BaAGee\Log\Log;
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
        $sessionConfig = Config::get('app/session');
        if (!empty($sessionConfig)) {
            Session::init($sessionConfig);
            Log::info('session init');
        }
        return $next($data);
    }
}
